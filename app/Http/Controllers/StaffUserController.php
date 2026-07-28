<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StaffUserController extends Controller
{
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

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('fname')->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('staff.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $zones = Zone::where('is_active', true)->orderBy('name')->get();

        return view('staff.create', compact('roles', 'zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname'    => 'required|string|max:100',
            'mname'    => 'nullable|string|max:100',
            'lname'    => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'zone_id'  => 'nullable|exists:zones,id',
            'is_active' => 'boolean',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'exists:roles,name',
        ]);

        $user = User::create([
            'fname'    => $validated['fname'],
            'mname'    => $validated['mname'] ?? '',
            'lname'    => $validated['lname'],
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'zone_id'  => $validated['zone_id'],
            'is_active' => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()->route('staff.index')->with('success', "User {$user->username} created successfully.");
    }

    public function edit(User $user)
    {
        $user->load('roles', 'zone');
        $roles = Role::orderBy('name')->get();
        $zones = Zone::where('is_active', true)->orderBy('name')->get();

        return view('staff.edit', compact('user', 'roles', 'zones'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fname'    => 'required|string|max:100',
            'mname'    => 'nullable|string|max:100',
            'lname'    => 'required|string|max:100',
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'zone_id'  => 'nullable|exists:zones,id',
            'is_active' => 'boolean',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'exists:roles,name',
        ]);

        $user->update([
            'fname'    => $validated['fname'],
            'mname'    => $validated['mname'] ?? '',
            'lname'    => $validated['lname'],
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'zone_id'  => $validated['zone_id'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles($validated['roles']);

        return redirect()->route('staff.index')->with('success', "User {$user->username} updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('staff.index')->with('success', 'User deleted.');
    }
}
