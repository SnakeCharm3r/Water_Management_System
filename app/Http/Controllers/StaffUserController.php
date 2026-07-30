<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Zone;
use App\Services\AuditService;
use App\Services\ZoneAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StaffUserController extends Controller
{
    public function __construct(private readonly ZoneAccessService $zoneAccess) {}

    public function index(Request $request)
    {
        $query = User::with(['roles', 'zone']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($zone = $request->input('zone')) {
            $query->whereHas('zones', fn ($q) => $q->where('zones.id', $zone));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->with(['roles', 'zones'])->orderBy('fname')->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $zones = $this->assignableZones($request->user());

        return view('staff.index', compact('users', 'roles', 'zones'));
    }

    public function create(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $zones = $this->assignableZones($request->user());

        return view('staff.create', compact('roles', 'zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'mname' => 'nullable|string|max:100',
            'lname' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'boolean',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => ['exists:zones,id', function ($attribute, $value, $fail) use ($request) {
                $zone = Zone::find($value);
                if (! $zone || ! $zone->is_active) {
                    $fail('Inactive zone cannot be assigned.');
                }
                if (! $this->zoneAccess->canAccess($request->user(), $zone)) {
                    $fail('Unauthorized zone selected.');
                }
            }],
            'primary_zone_id' => ['nullable', Rule::in($request->input('zone_ids', []))],
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'fname' => $validated['fname'],
                'mname' => $validated['mname'] ?? '',
                'lname' => $validated['lname'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'zone_id' => $validated['primary_zone_id'] ?? ($validated['zone_ids'][0] ?? null),
                'is_active' => $validated['is_active'] ?? true,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles($validated['roles']);
            $this->syncZoneAssignments($user, $validated['zone_ids'] ?? [], $validated['primary_zone_id'] ?? null, $request->user());

            AuditService::log('created', $user, null, $user->toArray());

            return $user;
        });

        return redirect()->route('staff.index')->with('success', "User {$user->username} created successfully.");
    }

    public function edit(Request $request, User $user)
    {
        $user->load(['roles', 'zones']);
        $roles = Role::orderBy('name')->get();
        $zones = $this->assignableZones($request->user());

        return view('staff.edit', compact('user', 'roles', 'zones'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'mname' => 'nullable|string|max:100',
            'lname' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'is_active' => 'boolean',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => ['exists:zones,id', function ($attribute, $value, $fail) use ($request) {
                $zone = Zone::find($value);
                if (! $zone || ! $zone->is_active) {
                    $fail('Inactive zone cannot be assigned.');
                }
                if (! $this->zoneAccess->canAccess($request->user(), $zone)) {
                    $fail('Unauthorized zone selected.');
                }
            }],
            'primary_zone_id' => ['nullable', Rule::in($request->input('zone_ids', []))],
        ]);

        DB::transaction(function () use ($validated, $user, $request) {
            $old = $user->toArray();

            $user->update([
                'fname' => $validated['fname'],
                'mname' => $validated['mname'] ?? '',
                'lname' => $validated['lname'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'zone_id' => $validated['primary_zone_id'] ?? ($validated['zone_ids'][0] ?? null),
                'is_active' => $validated['is_active'] ?? false,
            ]);

            if (! empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $user->syncRoles($validated['roles']);
            $this->syncZoneAssignments($user, $validated['zone_ids'] ?? [], $validated['primary_zone_id'] ?? null, $request->user());

            AuditService::log('updated', $user, $old, $user->toArray());
        });

        return redirect()->route('staff.index')->with('success', "User {$user->username} updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->syncRoles([]);
        $user->zones()->detach();
        $user->delete();

        return redirect()->route('staff.index')->with('success', 'User deleted.');
    }

    private function assignableZones(User $user): array
    {
        if ($user->hasRole('super-admin') || $user->hasPermissionTo('zones.view-all')) {
            return Zone::where('is_active', true)->orderBy('name')->get()->map(fn ($z) => [
                'id' => $z->id, 'name' => $z->name, 'type' => $z->zone_type,
            ])->toArray();
        }

        return Zone::whereIn('id', $this->zoneAccess->accessibleZoneIds($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($z) => ['id' => $z->id, 'name' => $z->name, 'type' => $z->zone_type])
            ->toArray();
    }

    private function syncZoneAssignments(User $user, array $zoneIds, ?int $primaryZoneId, User $actor): void
    {
        $existing = $user->zones()->pluck('zones.id')->toArray();

        $sync = [];
        foreach ($zoneIds as $zoneId) {
            $sync[$zoneId] = [
                'is_primary' => (string) $zoneId === (string) $primaryZoneId,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
            ];
        }

        $user->zones()->sync($sync);
        $this->zoneAccess->clearCache($user);

        foreach (array_diff($existing, $zoneIds) as $removed) {
            AuditService::log('zone_unassigned', $user, ['zone_id' => $removed], ['zone_id' => null]);
        }

        foreach (array_diff($zoneIds, $existing) as $added) {
            AuditService::log('zone_assigned', $user, null, ['zone_id' => $added, 'is_primary' => (string) $added === (string) $primaryZoneId]);
        }
    }
}
