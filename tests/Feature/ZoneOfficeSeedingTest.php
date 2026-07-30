<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneOffice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneOfficeSeedingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_zone_show_renders_office_map(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super-admin');

        $hq = Zone::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'zone_type' => 'authority',
            'is_active' => true,
        ]);

        $reader = User::factory()->create();
        $reader->assignRole('meter-reader');

        ZoneOffice::create([
            'zone_id' => $hq->id,
            'name' => 'DAWASA Head Office',
            'office_type' => 'head_office',
            'address' => 'DAWASA Building, Morogoro Road, Ubungo, Dar es Salaam',
            'phone' => '+255 22 245 0511',
            'email' => 'info@dawasa.go.tz',
            'latitude' => -6.7924,
            'longitude' => 39.2083,
            'easting' => 523_017.00,
            'northing' => 9_249_112.00,
            'utm_zone' => '37M',
            'opening_time' => '08:00',
            'closing_time' => '16:30',
            'opening_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'is_main_office' => true,
            'manager_user_id' => $reader->id,
        ]);

        $response = $this->actingAs($admin)->get(route('zones.show', $hq));
        $response->assertOk();
        $response->assertSee('Zone Offices & Locations');
        $response->assertSee('leaflet');
    }
}
