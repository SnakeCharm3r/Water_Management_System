<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Zone;
use App\Services\AuditService;
use App\Services\DashboardStatisticsService;
use App\Services\ZoneAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ZoneController extends Controller
{
    public function __construct(
        private readonly ZoneAccessService $zoneAccess,
        private readonly DashboardStatisticsService $statsService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Zone::query()
            ->with(['parent', 'children'])
            ->withCount(['users', 'waterAccounts']);

        if (! $this->zoneAccess->hasAuthorityWideAccess($user)) {
            $query->whereIn('id', $this->zoneAccess->accessibleZoneIds($user));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('zone_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active');
        }

        if ($parent = $request->input('parent')) {
            $query->where('parent_id', $parent);
        }

        $zones = $query->orderBy('zone_type')->orderBy('name')->paginate(20)->withQueryString();
        $types = Zone::query()->distinct()->pluck('zone_type');
        $parents = Zone::query()->whereNull('parent_id')->orderBy('name')->get();

        return view('zones.index', compact('zones', 'types', 'parents'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $this->authorize('create', Zone::class);

        $parents = $this->eligibleParentZones($user);

        return view('zones.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorize('create', Zone::class);

        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:zones,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region' => 'nullable|string|max:128',
            'district' => 'nullable|string|max:128',
            'zone_type' => ['required', 'string', Rule::in(['authority', 'region', 'branch', 'operational_zone', 'service_area', 'meter_reading_route', 'zone'])],
            'parent_id' => ['nullable', 'exists:zones,id', function ($attribute, $value, $fail) use ($user) {
                if ($value && ! $this->zoneAccess->canAccess($user, Zone::find($value))) {
                    $fail('You cannot create a zone under an unauthorized parent.');
                }
            }],
        ]);

        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;
        $validated['is_active'] = true;

        $zone = DB::transaction(function () use ($validated) {
            $zone = Zone::create($validated);
            AuditService::log('created', $zone, null, $zone->toArray());

            return $zone;
        });

        return redirect()->route('zones.index')->with('success', "Zone '{$zone->name}' created.");
    }

    public function show(Zone $zone)
    {
        $this->authorize('view', $zone);

        $zone->load(['parent', 'children.users', 'users', 'offices.manager']);

        return view('zones.show', compact('zone'));
    }

    public function edit(Request $request, Zone $zone)
    {
        $user = $request->user();
        $this->authorize('update', $zone);

        $parents = $this->eligibleParentZones($user, $zone->id);

        return view('zones.edit', compact('zone', 'parents'));
    }

    public function update(Request $request, Zone $zone)
    {
        $user = $request->user();
        $this->authorize('update', $zone);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('zones', 'code')->ignore($zone->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region' => 'nullable|string|max:128',
            'district' => 'nullable|string|max:128',
            'zone_type' => ['required', 'string', Rule::in(['authority', 'region', 'branch', 'operational_zone', 'service_area', 'meter_reading_route', 'zone'])],
            'parent_id' => ['nullable', 'exists:zones,id', "not_in:{$zone->id}", function ($attribute, $value, $fail) use ($user, $zone) {
                if ($value && ! $this->zoneAccess->canAccess($user, Zone::find($value))) {
                    $fail('You cannot assign an unauthorized parent zone.');
                }
                if ($value && $this->wouldCreateCycle($zone, (int) $value)) {
                    $fail('This parent would create a circular zone hierarchy.');
                }
            }],
        ]);

        $validated['updated_by'] = $user->id;
        $old = $zone->toArray();

        DB::transaction(function () use ($zone, $validated, $old) {
            $zone->update($validated);
            AuditService::log('updated', $zone, $old, $zone->toArray());
        });

        return redirect()->route('zones.index')->with('success', "Zone '{$zone->name}' updated.");
    }

    public function toggleStatus(Request $request, Zone $zone)
    {
        $user = $request->user();
        $this->authorize('deactivate', $zone);

        $newStatus = ! $zone->is_active;
        $old = $zone->toArray();

        DB::transaction(function () use ($zone, $newStatus, $old, $user) {
            $zone->update(['is_active' => $newStatus, 'updated_by' => $user->id]);
            AuditService::log($newStatus ? 'activated' : 'deactivated', $zone, $old, $zone->toArray());
        });

        $label = $newStatus ? 'activated' : 'deactivated';

        return redirect()->route('zones.index')->with('success', "Zone '{$zone->name}' {$label}.");
    }

    public function assignStaff(Request $request, Zone $zone)
    {
        $this->authorize('assignUsers', $zone);

        $users = User::query()->where('is_active', true)->orderBy('fname')->get();

        return view('zones.assign-staff', compact('zone', 'users'));
    }

    public function updateStaff(Request $request, Zone $zone)
    {
        $this->authorize('assignUsers', $zone);

        $validated = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'primary_user_id' => ['nullable', Rule::in($request->input('user_ids', []))],
        ]);

        $userIds = $validated['user_ids'] ?? [];
        $primaryUserId = $validated['primary_user_id'] ?? null;

        DB::transaction(function () use ($zone, $userIds, $primaryUserId, $request) {
            $existing = $zone->users()->pluck('users.id')->toArray();

            // Detach users not in the list
            foreach (array_diff($existing, $userIds) as $removeUserId) {
                $zone->users()->detach($removeUserId);
                AuditService::log('unassigned', $zone, ['user_id' => $removeUserId], ['user_id' => null, 'zone_id' => $zone->id]);
            }

            // Sync assignments
            foreach ($userIds as $userId) {
                $isPrimary = (string) $userId === (string) $primaryUserId;
                $wasAssigned = in_array($userId, $existing, true);

                $zone->users()->syncWithoutDetaching([
                    $userId => [
                        'is_primary' => $isPrimary,
                        'assigned_by' => $request->user()->id,
                        'assigned_at' => now(),
                    ],
                ]);

                // Ensure only one primary per user
                DB::table('user_zone')
                    ->where('user_id', $userId)
                    ->where('zone_id', '!=', $zone->id)
                    ->update(['is_primary' => false]);

                if (! $wasAssigned) {
                    AuditService::log('assigned', $zone, null, ['user_id' => $userId, 'zone_id' => $zone->id, 'is_primary' => $isPrimary]);
                }

                app(ZoneAccessService::class)->clearCache(User::find($userId));
            }
        });

        return redirect()->route('zones.show', $zone)->with('success', 'Staff assignments updated.');
    }

    private function eligibleParentZones(User $user, ?int $excludeId = null): array
    {
        $query = Zone::query()->where('is_active', true);

        if (! $this->zoneAccess->hasAuthorityWideAccess($user)) {
            $query->whereIn('id', $this->zoneAccess->accessibleZoneIds($user));
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('name')->get()->map(fn ($z) => [
            'id' => $z->id,
            'name' => $z->name,
            'type' => $z->zone_type,
        ])->toArray();
    }

    private function wouldCreateCycle(Zone $zone, int $newParentId): bool
    {
        $current = Zone::find($newParentId);
        while ($current) {
            if ($current->id === $zone->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }
}
