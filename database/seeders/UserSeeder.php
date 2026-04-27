<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seeded = [
            ['email' => 'superadmin@ggaa.com', 'name' => 'Super Admin',    'role' => 'Super Admin'],
            ['email' => 'manager@ggaa.com',    'name' => 'Branch Manager', 'role' => 'Branch Manager'],
            ['email' => 'employee@ggaa.com',   'name' => 'Staff Employee', 'role' => 'Employee'],
            ['email' => 'client@ggaa.com',     'name' => 'Portal Client',  'role' => 'Client'],
        ];

        foreach ($seeded as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }
        }
    }
}
