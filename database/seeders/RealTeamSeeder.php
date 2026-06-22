<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\StaffUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RealTeamSeeder extends Seeder
{
    /**
     * Seeds the actual GGAA team (recovered from the production users dump).
     *
     * Every account starts on the shared "ggaa@password" credential and is
     * forced to set a personal password on first login (must_change_password).
     *
     * NOTE: the source dump carried no role information. Gedion is the GM
     * (Super Admin); the branch staff default to "Employee" below — adjust the
     * 'role' value for anyone who is a Branch Manager / Team Leader /
     * Finance Admin / Operation Manager, then re-run this seeder.
     */
    public function run(): void
    {
        $mainOffice = Branch::firstOrCreate(['name' => 'Main Office'], ['location' => 'HQ', 'is_active' => true]);
        $hawassa    = Branch::firstOrCreate(['name' => 'Hawassa Branch'], ['location' => 'Sidama Region, Hawassa', 'is_active' => true]);

        $team = [
            // General Manager
            ['name' => 'Gedion Gebre',       'email' => 'gedion.gebre@ggaa.com', 'role' => 'Super Admin', 'position' => 'admin',    'branch' => $mainOffice],

            // Branch staff (Hawassa) — set the real role per person.
            ['name' => 'Dereje Matewos',     'email' => 'derejemggaa@gmail.com',  'role' => 'Operation Manager', 'position' => 'operation_manager', 'branch' => $hawassa],
            ['name' => 'Selamu Gebre',       'email' => 'selamuggaa@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Tsegaye Teketel',    'email' => 'tontaggaa@gmail.com',    'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Dereje Birhanu',     'email' => 'derejeggaa@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Selamawit Mathewos', 'email' => 'sely62ggaa@gmail.com',   'role' => 'Finance Admin', 'position' => 'finance', 'branch' => $hawassa],
            ['name' => 'Bethelhem Mesfin',   'email' => 'betelhemggaa@gmail.com', 'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Birtukuan Desta',    'email' => 'burteggaa@gmail.com',    'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Desta Mussie',       'email' => 'destaggaa@gmail.com',    'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Feven Bekele',       'email' => 'fevenggaab@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Kalkidan Abebe',     'email' => 'kal.ggaa@gmail.com',     'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Samuel Legeto',      'email' => 'samuelggaa21@gmail.com', 'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Simiret Kebede',     'email' => 'simretggaa@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Nathan Asfaw',       'email' => 'nathanggaa@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
            ['name' => 'Efrem',              'email' => 'ephremggaa@gmail.com',   'role' => 'Employee', 'position' => 'employee', 'branch' => $hawassa],
        ];

        foreach ($team as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name'                 => $member['name'],
                    'password'             => Hash::make('ggaa@password'),
                    'must_change_password' => true,
                    'branch_id'            => $member['branch']->id,
                    'email_verified_at'    => now(),
                ]
            );

            $user->syncRoles([$member['role']]);

            StaffUser::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'position'        => $member['position'],
                    'employment_type' => 'full_time',
                    'is_active'       => true,
                ]
            );
        }
    }
}
