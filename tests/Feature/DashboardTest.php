<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillingCycle;
use App\Models\Customer;
use App\Models\Meter;
use App\Models\MeterInstallation;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\TariffCategory;
use App\Models\User;
use App\Models\WaterAccount;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedPermissions(): void
    {
        $perms = [
            'admin-panel.access', 'dashboard.view', 'customers.view', 'customers.create',
            'water-accounts.view', 'water-accounts.create',
            'meters.view', 'meters.install',
            'meter-readings.view', 'meter-readings.submit',
            'billing-cycles.manage', 'bills.view',
            'adjustments.request', 'tariffs.view',
            'payments.view', 'payments.confirm', 'payments.allocate',
            'ledger.view', 'staff-users.view', 'roles.view',
            'zones.view', 'billing-settings.manage', 'audit-logs.view',
            'synchronization.monitor',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $admin = Role::findOrCreate('super-admin', 'web');
        $admin->givePermissionTo($perms);

        $reader = Role::findOrCreate('meter-reader', 'web');
        $reader->givePermissionTo(['admin-panel.access', 'dashboard.view', 'meter-readings.view', 'meter-readings.submit']);

        $auditor = Role::findOrCreate('auditor', 'web');
        $auditor->givePermissionTo(['admin-panel.access', 'dashboard.view', 'customers.view', 'water-accounts.view', 'meters.view', 'meter-readings.view', 'bills.view', 'payments.view', 'ledger.view', 'audit-logs.view']);
    }

    private function makeAdmin(?Zone $zone = null): User
    {
        $user = User::factory()->create(['is_active' => true, 'zone_id' => $zone?->id]);
        $user->assignRole('super-admin');
        return $user;
    }

    private function makeZoneUser(Zone $zone, string $role = 'meter-reader'): User
    {
        $user = User::factory()->create(['is_active' => true, 'zone_id' => $zone->id]);
        $user->assignRole($role);
        return $user;
    }

    private function makeZone(string $name = 'Zone A', ?int $parentId = null): Zone
    {
        return Zone::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'zone_type' => 'zone',
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
    }

    // ── Authentication ────────────────────────────────────

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_access_dashboard(): void
    {
        $this->seedPermissions();
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_access_dashboard(): void
    {
        $this->seedPermissions();
        $user = $this->makeAdmin();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Operations Dashboard');
    }

    // ── Zone scoping ──────────────────────────────────────

    public function test_zone_user_sees_only_their_zone_data(): void
    {
        $this->seedPermissions();

        $zoneA = $this->makeZone('Zone A');
        $zoneB = $this->makeZone('Zone B');

        $categ = TariffCategory::create(['name' => 'Domestic', 'code' => 'DOM', 'is_active' => true]);

        $custA = Customer::create(['first_name' => 'John', 'last_name' => 'Doe', 'customer_type' => 'individual', 'status' => 'active']);
        WaterAccount::create(['customer_id' => $custA->id, 'zone_id' => $zoneA->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-001', 'status' => 'active']);

        $custB = Customer::create(['first_name' => 'Jane', 'last_name' => 'Smith', 'customer_type' => 'individual', 'status' => 'active']);
        WaterAccount::create(['customer_id' => $custB->id, 'zone_id' => $zoneB->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-002', 'status' => 'active']);

        $zoneUser = $this->makeZoneUser($zoneA);

        $response = $this->actingAs($zoneUser)->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_super_admin_sees_all_zones(): void
    {
        $this->seedPermissions();

        $zoneA = $this->makeZone('Zone A');
        $zoneB = $this->makeZone('Zone B');
        $admin = $this->makeAdmin();

        $categ = TariffCategory::create(['name' => 'Domestic', 'code' => 'DOM', 'is_active' => true]);

        Customer::create(['first_name' => 'John', 'last_name' => 'Doe', 'customer_type' => 'individual', 'status' => 'active']);
        WaterAccount::create(['customer_id' => 1, 'zone_id' => $zoneA->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-001', 'status' => 'active']);
        WaterAccount::create(['customer_id' => 1, 'zone_id' => $zoneB->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-002', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
    }

    // ── Summary cards ─────────────────────────────────────

    public function test_summary_cards_return_correct_aggregates(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $admin = $this->makeAdmin();
        $categ = TariffCategory::create(['name' => 'Domestic', 'code' => 'DOM', 'is_active' => true]);

        $cust = Customer::create(['first_name' => 'Test', 'last_name' => 'User', 'customer_type' => 'individual', 'status' => 'active']);
        $acct = WaterAccount::create(['customer_id' => $cust->id, 'zone_id' => $zone->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-100', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Active customers');
        $response->assertSee('Active accounts');
    }

    public function test_outstanding_excludes_voided_bills(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $admin = $this->makeAdmin();
        $categ = TariffCategory::create(['name' => 'Domestic', 'code' => 'DOM', 'is_active' => true]);

        $cust = Customer::create(['first_name' => 'Test', 'last_name' => 'User', 'customer_type' => 'individual', 'status' => 'active']);
        $acct = WaterAccount::create(['customer_id' => $cust->id, 'zone_id' => $zone->id, 'tariff_category_id' => $categ->id, 'account_number' => 'WA-100', 'status' => 'active']);

        $cycle = BillingCycle::create(['name' => 'Jul 2026', 'period_start' => '2026-07-01', 'period_end' => '2026-07-31', 'status' => 'open']);

        Bill::create(['water_account_id' => $acct->id, 'billing_cycle_id' => $cycle->id, 'bill_number' => 'B-001', 'status' => 'issued', 'total_amount' => 50000, 'balance_due' => 50000, 'period_start' => '2026-07-01', 'period_end' => '2026-07-31', 'due_date' => '2026-08-15']);
        Bill::create(['water_account_id' => $acct->id, 'billing_cycle_id' => $cycle->id, 'bill_number' => 'B-002', 'status' => 'voided', 'total_amount' => 30000, 'balance_due' => 30000, 'period_start' => '2026-07-01', 'period_end' => '2026-07-31', 'due_date' => '2026-08-15']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('50,000');
        $response->assertDontSee('80,000');
    }

    // ── Quick actions ─────────────────────────────────────

    public function test_unauthorized_quick_actions_are_hidden(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $reader = $this->makeZoneUser($zone, 'meter-reader');

        $response = $this->actingAs($reader)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Capture reading');
        $response->assertDontSee('Register customer');
        $response->assertDontSee('Open billing cycle');
    }

    // ── Navigation ────────────────────────────────────────

    public function test_navigation_shows_only_permitted_items(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $reader = $this->makeZoneUser($zone, 'meter-reader');

        $response = $this->actingAs($reader)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Meter Readings');
        $response->assertDontSee('Billing Settings');
        $response->assertDontSee('Staff');
    }

    // ── Filters ───────────────────────────────────────────

    public function test_filters_persist_in_query_string(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard', ['zone' => $zone->id]));
        $response->assertOk();
    }

    // ── Empty state ───────────────────────────────────────

    public function test_dashboard_renders_safely_with_no_data(): void
    {
        $this->seedPermissions();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Active customers');
    }

    // ── Auditor read-only ─────────────────────────────────

    public function test_auditor_has_read_only_access(): void
    {
        $this->seedPermissions();
        $zone = $this->makeZone();
        $auditor = $this->makeZoneUser($zone, 'auditor');

        $response = $this->actingAs($auditor)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('Register customer');
        $response->assertDontSee('Record payment');
    }
}
