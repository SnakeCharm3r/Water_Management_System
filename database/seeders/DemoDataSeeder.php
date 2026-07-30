<?php

namespace Database\Seeders;

use App\Models\AccountLedgerEntry;
use App\Models\Bill;
use App\Models\BillingCycle;
use App\Models\Customer;
use App\Models\Meter;
use App\Models\MeterInstallation;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TariffBlock;
use App\Models\TariffCategory;
use App\Models\TariffRate;
use App\Models\User;
use App\Models\WaterAccount;
use App\Models\Zone;
use App\Models\ZoneOffice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Zones (DAWASA DSM structure) ────────────────
        $hq = Zone::create(['name' => 'Head Office', 'code' => 'HQ', 'zone_type' => 'authority', 'is_active' => true]);
        $kinondoni = Zone::create(['name' => 'Kinondoni Region', 'code' => 'KIN', 'zone_type' => 'region', 'parent_id' => $hq->id, 'is_active' => true]);
        $ilala = Zone::create(['name' => 'Ilala Region', 'code' => 'ILA', 'zone_type' => 'region', 'parent_id' => $hq->id, 'is_active' => true]);
        $temeke = Zone::create(['name' => 'Temeke Region', 'code' => 'TEM', 'zone_type' => 'region', 'parent_id' => $hq->id, 'is_active' => true]);

        $kinZ1 = Zone::create(['name' => 'Kinondoni Zone A', 'code' => 'KIN-A', 'zone_type' => 'zone', 'parent_id' => $kinondoni->id, 'is_active' => true]);
        $kinZ2 = Zone::create(['name' => 'Kinondoni Zone B', 'code' => 'KIN-B', 'zone_type' => 'zone', 'parent_id' => $kinondoni->id, 'is_active' => true]);
        $ilaZ1 = Zone::create(['name' => 'Ilala Zone A', 'code' => 'ILA-A', 'zone_type' => 'zone', 'parent_id' => $ilala->id, 'is_active' => true]);
        $temZ1 = Zone::create(['name' => 'Temeke Zone A', 'code' => 'TEM-A', 'zone_type' => 'zone', 'parent_id' => $temeke->id, 'is_active' => true]);

        // ── 2. Staff users with roles ──────────────────────
        $password = Hash::make('123.test');

        // Update existing admin to HQ zone
        $admin = User::where('username', 'admin')->first();
        if ($admin) {
            $admin->update(['zone_id' => $hq->id]);
            $admin->zones()->sync([
                $hq->id => ['is_primary' => true, 'assigned_by' => $admin->id, 'assigned_at' => now()],
            ]);
        }

        $staffData = [
            ['fname' => 'Juma',    'mname' => 'H.',   'lname' => 'Mwinyigoha', 'username' => 'juma.rm',     'email' => 'juma@dawasa.local',     'zone_id' => $kinondoni->id, 'zones' => [$kinondoni->id], 'role' => 'regional-manager'],
            ['fname' => 'Fatma',   'mname' => 'A.',   'lname' => 'Kibwana',    'username' => 'fatma.lm',    'email' => 'fatma@dawasa.local',    'zone_id' => $kinZ1->id,     'zones' => [$kinZ1->id], 'role' => 'line-manager'],
            ['fname' => 'Baraka',  'mname' => '',     'lname' => 'Mfaume',     'username' => 'baraka.cs',   'email' => 'baraka@dawasa.local',   'zone_id' => $ilaZ1->id,     'zones' => [$ilaZ1->id], 'role' => 'customer-service'],
            ['fname' => 'Asha',    'mname' => 'M.',   'lname' => 'Mzee',       'username' => 'asha.ms',     'email' => 'asha@dawasa.local',     'zone_id' => $kinZ1->id,     'zones' => [$kinZ1->id], 'role' => 'meter-supervisor'],
            ['fname' => 'Hamisi',  'mname' => '',     'lname' => 'Dachi',      'username' => 'hamisi.mr',   'email' => 'hamisi@dawasa.local',   'zone_id' => $kinZ1->id,     'zones' => [$kinZ1->id], 'role' => 'meter-reader'],
            ['fname' => 'Grace',   'mname' => 'P.',   'lname' => 'Mbuguni',    'username' => 'grace.mr2',   'email' => 'grace@dawasa.local',    'zone_id' => $ilaZ1->id,     'zones' => [$ilaZ1->id], 'role' => 'meter-reader'],
            ['fname' => 'Omari',   'mname' => '',     'lname' => 'Kondo',      'username' => 'omari.tech',  'email' => 'omari@dawasa.local',    'zone_id' => $temZ1->id,     'zones' => [$temZ1->id], 'role' => 'technician'],
            ['fname' => 'Neema',   'mname' => 'J.',   'lname' => 'Tarimo',     'username' => 'neema.bo',    'email' => 'neema@dawasa.local',    'zone_id' => $hq->id,        'zones' => [$hq->id], 'role' => 'billing-officer'],
            ['fname' => 'Daniel',  'mname' => 'R.',   'lname' => 'Mushi',      'username' => 'daniel.aud',  'email' => 'daniel@dawasa.local',   'zone_id' => $hq->id,        'zones' => [$hq->id], 'role' => 'auditor'],
            ['fname' => 'Rehema',  'mname' => '',     'lname' => 'Sanga',      'username' => 'rehema.sa',   'email' => 'rehema@dawasa.local',   'zone_id' => $hq->id,        'zones' => [$hq->id], 'role' => 'system-admin'],
        ];

        foreach ($staffData as $s) {
            $role = $s['role'];
            $zones = $s['zones'] ?? [];
            $primary = $s['zone_id'];
            unset($s['role'], $s['zones'], $s['zone_id']);
            $user = User::create(array_merge($s, [
                'zone_id' => $primary,
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ]));
            $user->assignRole($role);

            $sync = [];
            foreach ($zones as $zoneId) {
                $sync[$zoneId] = ['is_primary' => $zoneId === $primary, 'assigned_by' => $admin?->id, 'assigned_at' => now()];
            }
            $user->zones()->sync($sync);
        }

        // ── 2b. Zone offices (DAWASA offices in Dar es Salaam) ──
        $managerLookup = [
            'HQ' => User::where('username', 'rehema.sa')->value('id'),
            'KIN' => User::where('username', 'juma.rm')->value('id'),
            'KIN-A' => User::where('username', 'fatma.lm')->value('id'),
            'ILA-A' => User::where('username', 'baraka.cs')->value('id'),
            'TEM-A' => User::where('username', 'omari.tech')->value('id'),
        ];

        $officeData = [
            [
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
                'manager_user_id' => $managerLookup['HQ'],
            ],
            [
                'zone_id' => $kinondoni->id,
                'name' => 'Kinondoni Regional Office',
                'office_type' => 'regional_office',
                'address' => 'Kinondoni Municipal Offices Area, Dar es Salaam',
                'phone' => '+255 22 270 0000',
                'email' => 'kinondoni@dawasa.go.tz',
                'latitude' => -6.7833,
                'longitude' => 39.2667,
                'easting' => 529_412.00,
                'northing' => 9_250_102.00,
                'utm_zone' => '37M',
                'opening_time' => '08:00',
                'closing_time' => '15:30',
                'opening_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'is_main_office' => true,
                'manager_user_id' => $managerLookup['KIN'],
            ],
            [
                'zone_id' => $kinZ1->id,
                'name' => 'Kinondoni Zone A Customer Care Centre',
                'office_type' => 'customer_care',
                'address' => 'Kijitonyama, Kinondoni Zone A, Dar es Salaam',
                'phone' => '+255 22 277 1234',
                'email' => 'kinondoni.a@dawasa.go.tz',
                'latitude' => -6.7654,
                'longitude' => 39.2412,
                'easting' => 526_603.00,
                'northing' => 9_252_094.00,
                'utm_zone' => '37M',
                'opening_time' => '08:00',
                'closing_time' => '15:30',
                'opening_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'is_main_office' => true,
                'manager_user_id' => $managerLookup['KIN-A'],
            ],
            [
                'zone_id' => $ilaZ1->id,
                'name' => 'Ilala Zone A Customer Care Centre',
                'office_type' => 'customer_care',
                'address' => 'Ilala, Dar es Salaam',
                'phone' => '+255 22 212 3456',
                'email' => 'ilala.a@dawasa.go.tz',
                'latitude' => -6.8245,
                'longitude' => 39.2695,
                'easting' => 529_742.00,
                'northing' => 9_245_537.00,
                'utm_zone' => '37M',
                'opening_time' => '08:00',
                'closing_time' => '15:30',
                'opening_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'is_main_office' => true,
                'manager_user_id' => $managerLookup['ILA-A'],
            ],
            [
                'zone_id' => $temZ1->id,
                'name' => 'Temeke Zone A Customer Care Centre',
                'office_type' => 'customer_care',
                'address' => 'Temeke, Dar es Salaam',
                'phone' => '+255 22 285 6789',
                'email' => 'temeke.a@dawasa.go.tz',
                'latitude' => -6.8641,
                'longitude' => 39.2524,
                'easting' => 527_835.00,
                'northing' => 9_241_157.00,
                'utm_zone' => '37M',
                'opening_time' => '08:00',
                'closing_time' => '15:30',
                'opening_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'is_main_office' => true,
                'manager_user_id' => $managerLookup['TEM-A'],
            ],
        ];

        foreach ($officeData as $office) {
            ZoneOffice::create($office);
        }

        // ── 3. Tariff categories ───────────────────────────
        $domestic = TariffCategory::create(['name' => 'Domestic', 'code' => 'DOM', 'description' => 'Residential customers', 'customer_class' => 'residential', 'is_active' => true]);
        $commercial = TariffCategory::create(['name' => 'Commercial', 'code' => 'COM', 'description' => 'Business and commercial', 'customer_class' => 'commercial', 'is_active' => true]);
        $govt = TariffCategory::create(['name' => 'Government', 'code' => 'GOV', 'description' => 'Government institutions', 'customer_class' => 'government', 'is_active' => true]);

        // Domestic tariff rate — based on DAWASA bill structure
        $domRate = TariffRate::create([
            'tariff_category_id' => $domestic->id,
            'charge_type' => 'consumption',
            'effective_from' => '2012-07-01',
            'minimum_charge' => 0,
            'fixed_charge' => 0,
            'unit_rate' => null,
            'is_active' => true,
        ]);
        // Block tariff: 0-5 m³ @ TZS 511, 5-25 @ TZS 963, 25-50 @ TZS 1327, 50+ @ TZS 1599
        TariffBlock::create(['tariff_rate_id' => $domRate->id, 'sequence' => 1, 'from_quantity' => 0, 'to_quantity' => 5, 'rate_per_unit' => 511.00]);
        TariffBlock::create(['tariff_rate_id' => $domRate->id, 'sequence' => 2, 'from_quantity' => 5, 'to_quantity' => 25, 'rate_per_unit' => 963.00]);
        TariffBlock::create(['tariff_rate_id' => $domRate->id, 'sequence' => 3, 'from_quantity' => 25, 'to_quantity' => 50, 'rate_per_unit' => 1327.00]);
        TariffBlock::create(['tariff_rate_id' => $domRate->id, 'sequence' => 4, 'from_quantity' => 50, 'to_quantity' => null, 'rate_per_unit' => 1599.00]);

        $comRate = TariffRate::create([
            'tariff_category_id' => $commercial->id,
            'charge_type' => 'consumption',
            'effective_from' => '2012-07-01',
            'minimum_charge' => 5000,
            'fixed_charge' => 2500,
            'unit_rate' => 1850.00,
            'is_active' => true,
        ]);

        // ── 4. Customers and accounts (from bill data) ─────

        // Customer from the bill — Domestic account with meter 34231616
        $cust1 = Customer::create([
            'customer_number' => 'CUST-0001',
            'customer_type' => 'individual',
            'first_name' => 'Mwanaisha',
            'middle_name' => 'K.',
            'last_name' => 'Abdallah',
            'phone' => '0712345678',
            'status' => 'active',
        ]);

        $acct1 = WaterAccount::create([
            'customer_id' => $cust1->id,
            'zone_id' => $kinZ1->id,
            'tariff_category_id' => $domestic->id,
            'ip_number' => 'IP-2012-001',
            'account_name' => 'Mwanaisha K. Abdallah',
            'service_address' => 'Plot 45, Mwenge, Kinondoni, Dar es Salaam',
            'region' => 'Dar es Salaam',
            'district' => 'Kinondoni',
            'ward' => 'Mwenge',
            'street' => 'Bagamoyo Road',
            'plot_number' => '45',
            'status' => 'active',
            'opened_at' => '2012-01-15',
        ]);

        // More customers for variety
        $cust2 = Customer::create(['customer_number' => 'CUST-0002', 'customer_type' => 'individual', 'first_name' => 'Rashidi', 'last_name' => 'Mwamba', 'phone' => '0713456789', 'status' => 'active']);
        $acct2 = WaterAccount::create(['customer_id' => $cust2->id, 'zone_id' => $kinZ1->id, 'tariff_category_id' => $domestic->id, 'ip_number' => 'IP-2012-002', 'account_name' => 'Rashidi Mwamba', 'service_address' => 'Plot 12, Sinza, Kinondoni', 'region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Sinza', 'status' => 'active', 'opened_at' => '2012-03-01']);

        $cust3 = Customer::create(['customer_number' => 'CUST-0003', 'customer_type' => 'individual', 'first_name' => 'Anna', 'middle_name' => 'P.', 'last_name' => 'Kessy', 'phone' => '0714567890', 'status' => 'active']);
        $acct3 = WaterAccount::create(['customer_id' => $cust3->id, 'zone_id' => $kinZ2->id, 'tariff_category_id' => $domestic->id, 'ip_number' => 'IP-2012-003', 'account_name' => 'Anna P. Kessy', 'service_address' => 'Plot 78, Mikocheni, Kinondoni', 'region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Mikocheni', 'status' => 'active', 'opened_at' => '2012-05-10']);

        $cust4 = Customer::create(['customer_number' => 'CUST-0004', 'customer_type' => 'business', 'business_name' => 'Kilimanjaro Hotel Ltd', 'phone' => '0222111222', 'registration_number' => 'BRN-2005-1234', 'status' => 'active']);
        $acct4 = WaterAccount::create(['customer_id' => $cust4->id, 'zone_id' => $ilaZ1->id, 'tariff_category_id' => $commercial->id, 'ip_number' => 'IP-2010-004', 'account_name' => 'Kilimanjaro Hotel Ltd', 'service_address' => 'Kivukoni Front, Ilala, Dar es Salaam', 'region' => 'Dar es Salaam', 'district' => 'Ilala', 'ward' => 'Kivukoni', 'status' => 'active', 'opened_at' => '2010-06-01']);

        $cust5 = Customer::create(['customer_number' => 'CUST-0005', 'customer_type' => 'individual', 'first_name' => 'Josephat', 'last_name' => 'Mangi', 'phone' => '0715678901', 'status' => 'active']);
        $acct5 = WaterAccount::create(['customer_id' => $cust5->id, 'zone_id' => $temZ1->id, 'tariff_category_id' => $domestic->id, 'ip_number' => 'IP-2013-005', 'account_name' => 'Josephat Mangi', 'service_address' => 'Plot 20, Temeke, Dar es Salaam', 'region' => 'Dar es Salaam', 'district' => 'Temeke', 'ward' => 'Temeke', 'status' => 'active', 'opened_at' => '2013-02-20']);

        $cust6 = Customer::create(['customer_number' => 'CUST-0006', 'customer_type' => 'individual', 'first_name' => 'Saida', 'last_name' => 'Hassan', 'phone' => '0716789012', 'status' => 'suspended']);
        $acct6 = WaterAccount::create(['customer_id' => $cust6->id, 'zone_id' => $ilaZ1->id, 'tariff_category_id' => $domestic->id, 'ip_number' => 'IP-2011-006', 'account_name' => 'Saida Hassan', 'service_address' => 'Kariakoo, Ilala', 'region' => 'Dar es Salaam', 'district' => 'Ilala', 'ward' => 'Kariakoo', 'status' => 'suspended', 'suspended_at' => now()->subDays(45)]);

        // ── 5. Meters and installations ────────────────────

        // Meter from the bill: 34231616
        $meter1 = Meter::create(['meter_number' => '34231616', 'serial_number' => 'SN-34231616', 'meter_type' => 'mechanical', 'status' => 'installed', 'manufacturer' => 'Zenner', 'model' => 'MTKD-N', 'meter_size' => '15mm']);
        $inst1 = MeterInstallation::create(['water_account_id' => $acct1->id, 'meter_id' => $meter1->id, 'installation_date' => '2012-01-15', 'initial_reading' => 0, 'is_active' => true, 'status' => 'active']);

        $meter2 = Meter::create(['meter_number' => '44512890', 'serial_number' => 'SN-44512890', 'meter_type' => 'mechanical', 'status' => 'installed', 'manufacturer' => 'Zenner', 'model' => 'MTKD-N', 'meter_size' => '15mm']);
        $inst2 = MeterInstallation::create(['water_account_id' => $acct2->id, 'meter_id' => $meter2->id, 'installation_date' => '2012-03-01', 'initial_reading' => 0, 'is_active' => true, 'status' => 'active']);

        $meter3 = Meter::create(['meter_number' => '55678123', 'serial_number' => 'SN-55678123', 'meter_type' => 'mechanical', 'status' => 'installed', 'manufacturer' => 'Itron', 'model' => 'Aquadis+', 'meter_size' => '20mm']);
        $inst3 = MeterInstallation::create(['water_account_id' => $acct3->id, 'meter_id' => $meter3->id, 'installation_date' => '2012-05-10', 'initial_reading' => 0, 'is_active' => true, 'status' => 'active']);

        $meter4 = Meter::create(['meter_number' => '66890456', 'serial_number' => 'SN-66890456', 'meter_type' => 'mechanical', 'status' => 'installed', 'manufacturer' => 'Itron', 'model' => 'Aquadis+', 'meter_size' => '50mm']);
        $inst4 = MeterInstallation::create(['water_account_id' => $acct4->id, 'meter_id' => $meter4->id, 'installation_date' => '2010-06-01', 'initial_reading' => 0, 'is_active' => true, 'status' => 'active']);

        $meter5 = Meter::create(['meter_number' => '77901234', 'serial_number' => 'SN-77901234', 'meter_type' => 'mechanical', 'status' => 'installed', 'manufacturer' => 'Zenner', 'model' => 'MTKD-N', 'meter_size' => '15mm']);
        $inst5 = MeterInstallation::create(['water_account_id' => $acct5->id, 'meter_id' => $meter5->id, 'installation_date' => '2013-02-20', 'initial_reading' => 0, 'is_active' => true, 'status' => 'active']);

        // Faulty meter (no installation — removed)
        Meter::create(['meter_number' => '88012345', 'serial_number' => 'SN-88012345', 'meter_type' => 'mechanical', 'status' => 'faulty', 'manufacturer' => 'Zenner']);

        // Available spare meter
        Meter::create(['meter_number' => '99123456', 'serial_number' => 'SN-99123456', 'meter_type' => 'mechanical', 'status' => 'available', 'manufacturer' => 'Itron']);

        // Account without a meter (acct6 — suspended, no install)

        // ── 6. Billing cycles ──────────────────────────────

        $reader = User::where('username', 'hamisi.mr')->first();
        $billingOfficer = User::where('username', 'neema.bo')->first();

        // Past closed cycles
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $status = $i === 0 ? 'reading' : 'closed';

            $cycle = BillingCycle::create([
                'cycle_code' => 'CYC-'.$start->format('Y-m'),
                'name' => $start->format('F Y'),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'reading_start_date' => $start->copy()->addDays(20)->toDateString(),
                'reading_end_date' => $end->copy()->subDays(2)->toDateString(),
                'issue_date' => $end->copy()->addDays(1)->toDateString(),
                'due_date' => $end->copy()->addDays(7)->toDateString(),
                'status' => $status,
                'created_by' => $billingOfficer?->id,
            ]);
            $months[$i] = $cycle;
        }

        // ── 7. Meter readings — match bill data ────────────
        // Bill shows: meter 34231616, reading 1: 0→282 (282 m³), reading 2: 282→283 (1 m³)

        $readingSeq = [
            // acct1 meter — based on actual bill
            [$inst1, [0, 282], [282, 283], [283, 290], [290, 310], [310, 340]],
            // other meters
            [$inst2, [0, 15],  [15, 30],   [30, 48],   [48, 62],   [62, 80]],
            [$inst3, [0, 22],  [22, 45],   [45, 70],   [70, 95],   [95, 120]],
            [$inst4, [0, 500], [500, 950], [950, 1400], [1400, 1850], [1850, 2300]],
            [$inst5, [0, 10],  [10, 25],   [25, 38],   [38, 52],   [52, 65]],
        ];

        foreach ($readingSeq as $seq) {
            $installation = $seq[0];
            $prevReadingId = null;

            for ($i = 5; $i >= 1; $i--) {
                $pair = $seq[6 - $i] ?? null;
                if (! $pair || ! isset($months[$i])) {
                    continue;
                }

                $cycle = $months[$i];
                $prev = $pair[0];
                $curr = $pair[1];
                $consumption = $curr - $prev;

                $status = $i === 1 ? 'submitted' : 'verified';
                $reading = MeterReading::create([
                    'meter_installation_id' => $installation->id,
                    'billing_cycle_id' => $cycle->id,
                    'previous_reading_id' => $prevReadingId,
                    'reading_date' => $cycle->reading_start_date ?? $cycle->period_start,
                    'previous_reading' => $prev,
                    'current_reading' => $curr,
                    'consumption' => $consumption,
                    'reading_type' => 'actual',
                    'reading_status' => $status,
                    'reader_id' => $reader?->id,
                    'submitted_at' => now()->subMonths($i),
                    'verified_by' => $status === 'verified' ? $billingOfficer?->id : null,
                    'verified_at' => $status === 'verified' ? now()->subMonths($i)->addDays(2) : null,
                ]);
                $prevReadingId = $reading->id;
            }
        }

        // Current cycle: some readings submitted, some not yet done
        $currentCycle = $months[0];
        // Only acct1, acct2 have readings for current cycle
        MeterReading::create([
            'meter_installation_id' => $inst1->id, 'billing_cycle_id' => $currentCycle->id,
            'reading_date' => now()->subDays(3)->toDateString(),
            'previous_reading' => 340, 'current_reading' => 358, 'consumption' => 18,
            'reading_type' => 'actual', 'reading_status' => 'submitted',
            'reader_id' => $reader?->id, 'submitted_at' => now()->subDays(3),
        ]);
        MeterReading::create([
            'meter_installation_id' => $inst2->id, 'billing_cycle_id' => $currentCycle->id,
            'reading_date' => now()->subDays(2)->toDateString(),
            'previous_reading' => 80, 'current_reading' => 95, 'consumption' => 15,
            'reading_type' => 'actual', 'reading_status' => 'submitted',
            'reader_id' => $reader?->id, 'submitted_at' => now()->subDays(2),
        ]);

        // ── 8. Bills — based on bill image data ────────────

        $accounts = [
            [$acct1, $inst1, 'Mwanaisha K. Abdallah', 'Domestic'],
            [$acct2, $inst2, 'Rashidi Mwamba', 'Domestic'],
            [$acct3, $inst3, 'Anna P. Kessy', 'Domestic'],
            [$acct4, $inst4, 'Kilimanjaro Hotel Ltd', 'Commercial'],
            [$acct5, $inst5, 'Josephat Mangi', 'Domestic'],
        ];

        $billNum = 1;
        foreach ($accounts as [$acct, $inst, $custName, $tariffLabel]) {
            for ($i = 5; $i >= 1; $i--) {
                $cycle = $months[$i];

                $readings = MeterReading::where('meter_installation_id', $inst->id)
                    ->where('billing_cycle_id', $cycle->id)
                    ->where('reading_status', 'verified')
                    ->get();

                if ($readings->isEmpty()) {
                    continue;
                }

                $totalCharges = 0;
                $billItems = [];
                $seq = 1;

                foreach ($readings as $reading) {
                    // Simple rate: TZS 1,110 per m³ average for domestic (matches bill ~313k/282m³)
                    $unitRate = $tariffLabel === 'Commercial' ? 1850 : 1110;
                    $amount = round($reading->consumption * $unitRate, 2);
                    $totalCharges += $amount;

                    $billItems[] = [
                        'meter_reading_id' => $reading->id,
                        'item_type' => 'consumption',
                        'description' => 'Water consumption '.number_format($reading->consumption, 1).' m³',
                        'meter_number_snapshot' => $inst->meter->meter_number,
                        'category_snapshot' => $tariffLabel,
                        'reading_type_snapshot' => 'Actual',
                        'previous_reading' => $reading->previous_reading,
                        'current_reading' => $reading->current_reading,
                        'consumption' => $reading->consumption,
                        'unit_rate' => $unitRate,
                        'quantity' => $reading->consumption,
                        'amount' => $amount,
                        'sequence' => $seq++,
                    ];
                }

                // EWURA charge (from the bill: code 50/14, TZS 3,135.85)
                $ewura = round($totalCharges * 0.01, 2);
                $totalCharges += $ewura;
                $billItems[] = [
                    'item_type' => 'adjustment',
                    'description' => 'Debit notes - EWURA Charge (Code 50/14)',
                    'quantity' => 1,
                    'amount' => $ewura,
                    'sequence' => $seq++,
                ];

                $billStatus = $i >= 3 ? 'paid' : ($i === 2 ? 'partially_paid' : 'issued');
                $amountPaid = match ($billStatus) {
                    'paid' => $totalCharges,
                    'partially_paid' => round($totalCharges * 0.6, 2),
                    default => 0,
                };

                $bill = Bill::create([
                    'billing_cycle_id' => $cycle->id,
                    'water_account_id' => $acct->id,
                    'invoice_number' => 'INV-'.str_pad($billNum, 6, '0', STR_PAD_LEFT),
                    'bill_number' => 'BILL-'.str_pad($billNum, 6, '0', STR_PAD_LEFT),
                    'account_number_snapshot' => $acct->ip_number,
                    'customer_name_snapshot' => $custName,
                    'property_snapshot' => $acct->service_address,
                    'tariff_category_snapshot' => $tariffLabel,
                    'period_start' => $cycle->period_start,
                    'period_end' => $cycle->period_end,
                    'issued_at' => $cycle->issue_date,
                    'due_date' => $cycle->due_date,
                    'current_charges' => $totalCharges,
                    'adjustment_total' => $ewura,
                    'total_amount' => $totalCharges,
                    'amount_paid' => $amountPaid,
                    'balance_due' => $totalCharges - $amountPaid,
                    'status' => $billStatus,
                    'generated_by' => $billingOfficer?->id,
                    'approved_by' => $billingOfficer?->id,
                    'approved_at' => $cycle->issue_date,
                ]);

                foreach ($billItems as $item) {
                    $bill->items()->create($item);
                }

                // Create ledger entry for the bill
                AccountLedgerEntry::create([
                    'water_account_id' => $acct->id,
                    'bill_id' => $bill->id,
                    'entry_date' => $cycle->issue_date,
                    'entry_type' => 'bill',
                    'reference_number' => $bill->bill_number,
                    'description' => 'Bill for '.$cycle->name,
                    'debit_amount' => $totalCharges,
                    'credit_amount' => 0,
                    'running_balance' => $totalCharges - $amountPaid,
                    'idempotency_key' => 'bill-'.$bill->id,
                    'created_by' => $billingOfficer?->id,
                ]);

                $billNum++;
            }
        }

        // Add a voided bill (should NOT count in outstanding)
        $voidedBill = Bill::create([
            'billing_cycle_id' => $months[2]->id,
            'water_account_id' => $acct1->id,
            'invoice_number' => 'INV-VOID-001',
            'bill_number' => 'BILL-VOID-001',
            'account_number_snapshot' => $acct1->ip_number,
            'customer_name_snapshot' => 'Mwanaisha K. Abdallah',
            'property_snapshot' => $acct1->service_address,
            'tariff_category_snapshot' => 'Domestic',
            'period_start' => $months[2]->period_start,
            'period_end' => $months[2]->period_end,
            'issued_at' => $months[2]->issue_date,
            'due_date' => $months[2]->due_date,
            'total_amount' => 50000,
            'balance_due' => 50000,
            'status' => 'voided',
            'voided_by' => $billingOfficer?->id,
            'voided_at' => now()->subMonths(2),
            'void_reason' => 'Duplicate bill issued in error',
            'revision_number' => 2,
        ]);

        // ── 9. Payments ────────────────────────────────────

        $paymentNum = 1;
        $channels = ['m-pesa', 'bank_transfer', 'cash', 'tigo_pesa', 'selcom_pos'];
        $bills = Bill::whereIn('status', ['paid', 'partially_paid'])->get();

        foreach ($bills as $bill) {
            if ($bill->amount_paid <= 0) {
                continue;
            }

            $channel = $channels[array_rand($channels)];
            $payment = Payment::create([
                'water_account_id' => $bill->water_account_id,
                'receipt_number' => 'REC-'.str_pad($paymentNum, 6, '0', STR_PAD_LEFT),
                'payment_date' => $bill->due_date ?? now(),
                'amount' => $bill->amount_paid,
                'payment_method' => str_contains($channel, 'pesa') ? 'mobile_money' : ($channel === 'bank_transfer' ? 'bank_transfer' : 'cash'),
                'payment_channel' => $channel,
                'payer_name' => $bill->customer_name_snapshot,
                'status' => 'confirmed',
                'confirmed_at' => $bill->due_date ?? now(),
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'bill_id' => $bill->id,
                'allocated_amount' => $bill->amount_paid,
                'allocated_at' => now(),
            ]);

            AccountLedgerEntry::create([
                'water_account_id' => $bill->water_account_id,
                'payment_id' => $payment->id,
                'entry_date' => $bill->due_date ?? now(),
                'entry_type' => 'payment',
                'reference_number' => $payment->receipt_number,
                'description' => 'Payment via '.$channel,
                'debit_amount' => 0,
                'credit_amount' => $bill->amount_paid,
                'running_balance' => $bill->balance_due,
                'idempotency_key' => 'pay-'.$payment->id,
            ]);

            $paymentNum++;
        }

        // Pending (unconfirmed) payment
        Payment::create([
            'water_account_id' => $acct3->id,
            'receipt_number' => 'REC-PENDING-001',
            'payment_date' => now()->subDays(3),
            'amount' => 25000,
            'payment_method' => 'mobile_money',
            'payment_channel' => 'm-pesa',
            'payer_name' => 'Anna P. Kessy',
            'payer_phone' => '0714567890',
            'status' => 'pending',
        ]);

        // Reversed payment
        Payment::create([
            'water_account_id' => $acct4->id,
            'receipt_number' => 'REC-REV-001',
            'payment_date' => now()->subDays(10),
            'amount' => 100000,
            'payment_method' => 'bank_transfer',
            'payment_channel' => 'crdb_bank',
            'payer_name' => 'Kilimanjaro Hotel Ltd',
            'status' => 'reversed',
            'confirmed_at' => now()->subDays(10),
            'reversed_at' => now()->subDays(5),
            'reversal_reason' => 'Incorrect amount — customer requested reversal',
        ]);

        // Confirmed but unallocated payment
        Payment::create([
            'water_account_id' => $acct5->id,
            'receipt_number' => 'REC-UNALLOC-001',
            'payment_date' => now()->subDays(1),
            'amount' => 35000,
            'payment_method' => 'mobile_money',
            'payment_channel' => 'airtel_money',
            'payer_name' => 'Josephat Mangi',
            'status' => 'confirmed',
            'confirmed_at' => now()->subDays(1),
        ]);

        // ── 10. Update account balances ────────────────────

        foreach (WaterAccount::where('status', 'active')->get() as $acct) {
            $debit = AccountLedgerEntry::where('water_account_id', $acct->id)->sum('debit_amount');
            $credit = AccountLedgerEntry::where('water_account_id', $acct->id)->sum('credit_amount');
            $acct->update(['current_balance' => $debit - $credit]);
        }

        // ── 11. Integration outbox — a failed sync event ───
        DB::table('integration_outbox')->insert([
            'aggregate_type' => 'customer',
            'aggregate_id' => $cust1->id,
            'aggregate_uuid' => $cust1->public_uuid,
            'operation' => 'create',
            'payload' => json_encode(['id' => $cust1->id, 'name' => $cust1->display_name]),
            'idempotency_key' => 'demo-sync-fail-'.$cust1->id,
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'Connection timeout to Supabase',
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(1),
        ]);

        $this->command->info('Demo data seeded: 6 customers, 7 meters, 5 billing cycles, readings, bills, payments, and 10 staff users.');
        $this->command->newLine();
        $this->command->table(
            ['Username', 'Name', 'Role', 'Zone', 'Password'],
            collect($staffData)->map(fn ($s) => [$s['username'], trim($s['fname'].' '.$s['lname']), '-', '-', '123.test'])->toArray()
        );
        $this->command->info('Admin: admin / 123.test');
    }
}
