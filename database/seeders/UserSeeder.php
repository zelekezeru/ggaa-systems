<?php

namespace Database\Seeders;

use App\Models\StaffUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Public registration is disabled — the Super Admin is the only user
     * the system creates automatically. The remaining demo users below are
     * convenience accounts kept for local development.
     */
    public function run(): void
    {
        $seeded = [
            ['email' => 'superadmin@ggaa.com', 'name' => 'Super Admin',    'role' => 'Super Admin',    'position' => 'admin'],
        ];

        foreach ($seeded as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'                 => $data['name'],
                    'password'             => Hash::make('ggaa@password'),
                    'must_change_password' => true,
                    'email_verified_at'    => now(),
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }

            if ($data['position']) {
                StaffUser::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'position'        => $data['position'],
                        'employment_type' => 'full_time',
                        'is_active'       => true,
                    ]
                );
            }

            // Create a dummy branch and client record if it's the portal client to prevent the 403 error
            if ($data['role'] === 'Client' && is_null($user->client_id)) {
                $branch = \App\Models\Branch::firstOrCreate(
                    ['name' => 'Main Office'],
                    ['location' => 'HQ']
                );

                $client = \App\Models\Client::create([
                    'company_name' => 'Demo Client Corp',
                    'tin_number'   => '1234567890',
                    'sector'       => 'Service',
                    'branch_id'    => $branch->id,
                    'status'       => 'Active',
                ]);
                $user->update(['client_id' => $client->id]);
            }
        }
    }
}
