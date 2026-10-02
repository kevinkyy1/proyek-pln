<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Buat akun superadmin default.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Super Admin',
                'username' => 'admin',
                'email'    => 'admin@pln.local',
                'password' => Hash::make('123456'),
                'role'     => 'superadmin',
            ]
        );
    }
}
