<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin-panel.access', 'dashboard.view',
            'staff-users.view', 'staff-users.create', 'staff-users.update', 'staff-users.manage',
            'roles.view', 'roles.manage', 'zones.view', 'zones.manage',
            'customers.view', 'customers.create', 'customers.update',
            'water-accounts.view', 'water-accounts.create', 'water-accounts.update',
            'meters.view', 'meters.create', 'meters.update', 'meters.install', 'meters.replace',
            'meter-installations.view', 'meter-installations.manage',
            'meter-readings.view', 'meter-readings.submit', 'meter-readings.verify',
            'tariffs.view', 'tariffs.manage', 'billing-cycles.manage',
            'bills.view', 'bills.generate', 'bills.approve', 'bills.issue', 'bills.revise', 'bills.void',
            'adjustments.request', 'adjustments.approve',
            'payments.view', 'payments.confirm', 'payments.allocate', 'payments.reverse',
            'ledger.view', 'billing-settings.manage', 'audit-logs.view', 'synchronization.monitor',
            'complaints.view', 'complaints.assign', 'complaints.update',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $matrix = [
            'super-admin' => $permissions,
            'system-admin' => $permissions,
            'regional-manager' => ['admin-panel.access', 'dashboard.view', 'zones.view', 'customers.view', 'water-accounts.view', 'meters.view', 'meter-installations.view', 'meter-readings.view', 'bills.view', 'payments.view', 'ledger.view', 'complaints.view', 'complaints.assign', 'complaints.update'],
            'line-manager' => ['admin-panel.access', 'dashboard.view', 'customers.view', 'water-accounts.view', 'meters.view', 'meter-installations.view', 'meter-readings.view', 'meter-readings.verify', 'complaints.view', 'complaints.assign', 'complaints.update'],
            'customer-service' => ['admin-panel.access', 'dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'water-accounts.view', 'complaints.view', 'complaints.assign', 'complaints.update'],
            'meter-supervisor' => ['admin-panel.access', 'dashboard.view', 'meters.view', 'meters.create', 'meters.update', 'meters.install', 'meters.replace', 'meter-installations.view', 'meter-installations.manage', 'meter-readings.view', 'meter-readings.verify'],
            'meter-reader' => ['admin-panel.access', 'dashboard.view', 'water-accounts.view', 'meters.view', 'meter-installations.view', 'meter-readings.view', 'meter-readings.submit'],
            'technician' => ['admin-panel.access', 'dashboard.view', 'water-accounts.view', 'meters.view', 'meter-installations.view', 'meter-installations.manage', 'complaints.view', 'complaints.update'],
            'billing-officer' => ['admin-panel.access', 'dashboard.view', 'customers.view', 'water-accounts.view', 'meter-readings.view', 'tariffs.view', 'billing-cycles.manage', 'bills.view', 'bills.generate', 'adjustments.request', 'payments.view', 'payments.allocate', 'ledger.view'],
            'auditor' => ['admin-panel.access', 'dashboard.view', 'zones.view', 'customers.view', 'water-accounts.view', 'meters.view', 'meter-installations.view', 'meter-readings.view', 'tariffs.view', 'bills.view', 'payments.view', 'ledger.view', 'audit-logs.view'],
        ];

        foreach ($matrix as $roleName => $rolePermissions) {
            Role::findOrCreate($roleName, self::GUARD)->syncPermissions($rolePermissions);
        }

        $email = env('DAWASA_ADMIN_EMAIL');
        $password = env('DAWASA_ADMIN_PASSWORD');

        if (filled($email) && filled($password)) {
            $admin = User::query()->firstOrNew(['email' => $email]);
            $admin->fill([
                'fname' => env('DAWASA_ADMIN_FNAME', 'DAWASA'),
                'mname' => env('DAWASA_ADMIN_MNAME', ''),
                'lname' => env('DAWASA_ADMIN_LNAME', 'Administrator'),
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $admin->save();
            $admin->syncRoles(['super-admin']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
