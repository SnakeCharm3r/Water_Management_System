<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use App\Services\ZoneAccessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneAccessTest extends TestCase
{
    use RefreshDatabase;

    private ZoneAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->service = app(ZoneAccessService::class);
    }

    private function makeUser(string $roleName, array $zoneIds = [], ?int $primaryZoneId = null): User
    {
        $user = User::create([
            'fname' => 'Test',
            'lname' => 'User',
            'username' => 'test.'.uniqid(),
            'email' => 'test'.uniqid().'@dawasa.local',
            'password' => bcrypt('123.test'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($roleName);

        $primary = $primaryZoneId ?? ($zoneIds[0] ?? null);
        $user->update(['zone_id' => $primary]);

        $sync = [];
        foreach ($zoneIds as $id) {
            $sync[$id] = ['is_primary' => $id === $primary, 'assigned_at' => now()];
        }
        $user->zones()->sync($sync);

        return $user;
    }

    public function test_super_admin_can_access_all_zones(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $region = Zone::create(['code' => 'REG', 'name' => 'Region', 'zone_type' => 'region', 'parent_id' => $hq->id]);
        $admin = $this->makeUser('super-admin');

        $accessible = $this->service->accessibleZoneIds($admin);

        $this->assertContains($hq->id, $accessible);
        $this->assertContains($region->id, $accessible);
        $this->assertTrue($this->service->canAccess($admin, $region));
    }

    public function test_zone_user_sees_only_assigned_zone_records(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $zoneA = Zone::create(['code' => 'A', 'name' => 'Zone A', 'zone_type' => 'zone', 'parent_id' => $hq->id]);
        $zoneB = Zone::create(['code' => 'B', 'name' => 'Zone B', 'zone_type' => 'zone', 'parent_id' => $hq->id]);

        $reader = $this->makeUser('meter-reader', [$zoneA->id]);

        $accessible = $this->service->accessibleZoneIds($reader);
        $this->assertContains($zoneA->id, $accessible);
        $this->assertNotContains($zoneB->id, $accessible);
        $this->assertFalse($this->service->canAccess($reader, $zoneB));
    }

    public function test_regional_manager_sees_descendant_zones(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $region = Zone::create(['code' => 'REG', 'name' => 'Region', 'zone_type' => 'region', 'parent_id' => $hq->id]);
        $branch = Zone::create(['code' => 'BR', 'name' => 'Branch', 'zone_type' => 'branch', 'parent_id' => $region->id]);
        $otherRegion = Zone::create(['code' => 'OTH', 'name' => 'Other', 'zone_type' => 'region', 'parent_id' => $hq->id]);

        $manager = $this->makeUser('regional-manager', [$region->id]);

        $accessible = $this->service->accessibleZoneIds($manager);
        $this->assertContains($region->id, $accessible);
        $this->assertContains($branch->id, $accessible);
        $this->assertNotContains($otherRegion->id, $accessible);
    }

    public function test_user_assigned_multiple_zones_can_access_all(): void
    {
        $zone1 = Zone::create(['code' => 'Z1', 'name' => 'Zone 1', 'zone_type' => 'zone']);
        $zone2 = Zone::create(['code' => 'Z2', 'name' => 'Zone 2', 'zone_type' => 'zone']);

        $user = $this->makeUser('customer-service', [$zone1->id, $zone2->id], $zone1->id);

        $accessible = $this->service->accessibleZoneIds($user);
        $this->assertContains($zone1->id, $accessible);
        $this->assertContains($zone2->id, $accessible);
    }

    public function test_user_without_assignments_sees_no_scoped_data(): void
    {
        $zone = Zone::create(['code' => 'Z', 'name' => 'Zone', 'zone_type' => 'zone']);
        $user = $this->makeUser('meter-reader', []);

        $this->assertEmpty($this->service->accessibleZoneIds($user));
        $this->assertFalse($this->service->canAccess($user, $zone));
    }

    public function test_authority_wide_permission_allows_all_zones(): void
    {
        $zone = Zone::create(['code' => 'Z', 'name' => 'Zone', 'zone_type' => 'zone']);
        $user = $this->makeUser('auditor', []);
        $user->givePermissionTo('zones.view-all');

        $this->assertTrue($this->service->hasAuthorityWideAccess($user));
        $this->assertTrue($this->service->canAccess($user, $zone));
        $this->assertContains($zone->id, $this->service->accessibleZoneIds($user));
    }

    public function test_inactive_user_cannot_access_zones(): void
    {
        $zone = Zone::create(['code' => 'Z', 'name' => 'Zone', 'zone_type' => 'zone']);
        $user = $this->makeUser('super-admin');
        $user->update(['is_active' => false]);
        $user->refresh();

        $this->assertFalse($this->service->hasAuthorityWideAccess($user));
        $this->assertFalse($this->service->canAccess($user, $zone));
        $this->assertEmpty($this->service->accessibleZoneIds($user));
    }

    public function test_direct_access_to_out_of_scope_zone_returns_403(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $zoneA = Zone::create(['code' => 'A', 'name' => 'Zone A', 'zone_type' => 'zone', 'parent_id' => $hq->id]);
        $zoneB = Zone::create(['code' => 'B', 'name' => 'Zone B', 'zone_type' => 'zone', 'parent_id' => $hq->id]);

        $admin = User::where('username', 'admin')->first() ?? $this->makeUser('super-admin');
        $reader = $this->makeUser('meter-reader', [$zoneA->id]);

        $this->actingAs($reader)
            ->get(route('zones.show', $zoneB))
            ->assertForbidden();
    }

    public function test_user_cannot_create_zone_under_unauthorized_parent(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $zoneA = Zone::create(['code' => 'A', 'name' => 'Zone A', 'zone_type' => 'zone', 'parent_id' => $hq->id]);
        $zoneB = Zone::create(['code' => 'B', 'name' => 'Zone B', 'zone_type' => 'zone', 'parent_id' => $hq->id]);

        $manager = $this->makeUser('regional-manager', [$zoneA->id]);
        $manager->givePermissionTo(['zones.create']);

        $this->actingAs($manager)
            ->post(route('zones.store'), [
                'code' => 'NEW',
                'name' => 'New Zone',
                'zone_type' => 'zone',
                'parent_id' => $zoneB->id,
            ])
            ->assertSessionHasErrors(['parent_id']);
    }

    public function test_circular_zone_hierarchy_is_rejected(): void
    {
        $hq = Zone::create(['code' => 'HQ', 'name' => 'Head Office', 'zone_type' => 'authority']);
        $parent = Zone::create(['code' => 'P', 'name' => 'Parent', 'zone_type' => 'region', 'parent_id' => $hq->id]);
        $child = Zone::create(['code' => 'C', 'name' => 'Child', 'zone_type' => 'zone', 'parent_id' => $parent->id]);

        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)
            ->put(route('zones.update', $parent), [
                'code' => $parent->code,
                'name' => $parent->name,
                'zone_type' => $parent->zone_type,
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors(['parent_id']);
    }

    public function test_inactive_zone_cannot_be_assigned_to_user(): void
    {
        $activeZone = Zone::create(['code' => 'A', 'name' => 'Active', 'zone_type' => 'zone']);
        $inactiveZone = Zone::create(['code' => 'I', 'name' => 'Inactive', 'zone_type' => 'zone', 'is_active' => false]);

        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)
            ->post(route('staff.store'), [
                'fname' => 'New',
                'lname' => 'Staff',
                'username' => 'new.staff',
                'email' => 'new@dawasa.local',
                'password' => '123.test',
                'password_confirmation' => '123.test',
                'roles' => ['meter-reader'],
                'zone_ids' => [$inactiveZone->id],
            ])
            ->assertSessionHasErrors(['zone_ids.*']);
    }

    public function test_only_one_primary_zone_per_user(): void
    {
        $zone1 = Zone::create(['code' => 'Z1', 'name' => 'Zone 1', 'zone_type' => 'zone']);
        $zone2 = Zone::create(['code' => 'Z2', 'name' => 'Zone 2', 'zone_type' => 'zone']);

        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)
            ->post(route('staff.store'), [
                'fname' => 'New',
                'lname' => 'Staff',
                'username' => 'new.staff2',
                'email' => 'new2@dawasa.local',
                'password' => '123.test',
                'password_confirmation' => '123.test',
                'roles' => ['meter-reader'],
                'zone_ids' => [$zone1->id, $zone2->id],
                'primary_zone_id' => $zone1->id,
            ])
            ->assertRedirect(route('staff.index'));

        $user = User::where('username', 'new.staff2')->first();
        $this->assertEquals(1, $user->zones()->wherePivot('is_primary', true)->count());
        $this->assertEquals($zone1->id, $user->zones()->wherePivot('is_primary', true)->value('zones.id'));
    }

    public function test_zone_assignment_changes_clear_cache(): void
    {
        $zone1 = Zone::create(['code' => 'Z1', 'name' => 'Zone 1', 'zone_type' => 'zone']);
        $user = $this->makeUser('meter-reader', [$zone1->id]);

        // Prime cache
        $this->service->accessibleZoneIds($user);

        $zone2 = Zone::create(['code' => 'Z2', 'name' => 'Zone 2', 'zone_type' => 'zone']);
        $user->zones()->attach($zone2->id, ['is_primary' => false, 'assigned_at' => now()]);

        // After clearing, new zone should appear
        $this->service->clearCache($user);
        $this->assertContains($zone2->id, $this->service->accessibleZoneIds($user));
    }
}
