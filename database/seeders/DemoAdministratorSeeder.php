<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = User::query()->firstOrNew(['username' => 'admin']);
        $administrator->fill([
            'fname' => 'System',
            'mname' => '',
            'lname' => 'Administrator',
            'email' => 'admin@dawasa.local',
            'password' => Hash::make('123.test'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $administrator->save();
        $administrator->syncRoles(['super-admin']);
    }
}
