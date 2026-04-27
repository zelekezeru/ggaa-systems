<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // ── Service Types ────────────────────────────────────────────────────
        /** @var ServiceType $accounting */
        $accounting = ServiceType::firstOrCreate(
            ['slug' => 'accounting'],
            ['name' => 'Accounting', 'description' => 'Full bookkeeping and financial statement preparation.', 'complexity_weight' => 2, 'is_active' => true]
        );

        /** @var ServiceType $tax */
        $tax = ServiceType::firstOrCreate(
            ['slug' => 'tax'],
            ['name' => 'Tax', 'description' => 'ERCA tax filing, VAT returns, and compliance reporting.', 'complexity_weight' => 3, 'is_active' => true]
        );

        // ── Users ────────────────────────────────────────────────────────────
        /** @var User|null $manager */
        $manager  = User::where('email', 'manager@ggaa.com')->first();
        /** @var User|null $employee */
        $employee = User::where('email', 'employee@ggaa.com')->first();

        // ── Branches ─────────────────────────────────────────────────────────
        /** @var Branch $addis */
        $addis = Branch::firstOrCreate(
            ['name' => 'Addis Ababa Main'],
            ['location' => 'Bole Sub-City, Addis Ababa', 'manager_id' => $manager?->id, 'is_active' => true]
        );

        /** @var Branch $hawassa */
        $hawassa = Branch::firstOrCreate(
            ['name' => 'Hawassa Branch'],
            ['location' => 'Sidama Region, Hawassa', 'manager_id' => null, 'is_active' => true]
        );

        // Attach users to their branch.
        $manager?->update(['branch_id' => $addis->id]);
        $employee?->update(['branch_id' => $addis->id]);

        // ── Client definitions ───────────────────────────────────────────────
        // 'services' maps to ServiceType ids: 'accounting', 'tax', or 'both'
        $clients = [
            // Addis — Active
            [
                'company_name'         => 'Selam Trading PLC',
                'tin_number'           => '0012345678',
                'sector'               => 'Trade',
                'services'             => 'both',
                'branch_id'            => $addis->id,
                'assigned_employee_id' => $employee?->id,
                'status'               => 'Active',
                'complexity_score'     => 4,
            ],
            [
                'company_name'         => 'Yirgacheffe Coffee Exports',
                'tin_number'           => '0045678901',
                'sector'               => 'Coffee',
                'services'             => 'both',
                'branch_id'            => $addis->id,
                'assigned_employee_id' => $employee?->id,
                'status'               => 'Active',
                'complexity_score'     => 3,
            ],
            // Addis — Risk
            [
                'company_name'         => 'Nile Construction Group',
                'tin_number'           => '0056789012',
                'sector'               => 'Construction',
                'services'             => 'tax',
                'branch_id'            => $addis->id,
                'assigned_employee_id' => null,
                'status'               => 'Risk',
                'complexity_score'     => 5,
            ],
            [
                'company_name'         => 'Addis Garment Factory',
                'tin_number'           => '0067890123',
                'sector'               => 'Manufacturing',
                'services'             => 'both',
                'branch_id'            => $addis->id,
                'assigned_employee_id' => null,
                'status'               => 'Risk',
                'complexity_score'     => 4,
            ],
            // Hawassa — Active
            [
                'company_name'         => 'Sidama Coffee Union',
                'tin_number'           => '0090123456',
                'sector'               => 'Coffee',
                'services'             => 'both',
                'branch_id'            => $hawassa->id,
                'assigned_employee_id' => null,
                'status'               => 'Active',
                'complexity_score'     => 3,
            ],
            [
                'company_name'         => 'Rift Valley Textiles',
                'tin_number'           => '0101234567',
                'sector'               => 'Manufacturing',
                'services'             => 'accounting',
                'branch_id'            => $hawassa->id,
                'assigned_employee_id' => null,
                'status'               => 'Active',
                'complexity_score'     => 2,
            ],
            // Hawassa — Risk / Incomplete
            [
                'company_name'         => 'Hawassa Industrial Park Tenant Co.',
                'tin_number'           => '0112345678',
                'sector'               => 'Manufacturing',
                'services'             => 'tax',
                'branch_id'            => $hawassa->id,
                'assigned_employee_id' => null,
                'status'               => 'Risk',
                'complexity_score'     => 5,
            ],
            [
                'company_name'         => 'Massa Agri Cooperative',
                'tin_number'           => '0123456789',
                'sector'               => 'Agriculture',
                'services'             => 'accounting',
                'branch_id'            => $hawassa->id,
                'assigned_employee_id' => null,
                'status'               => 'Incomplete',
                'complexity_score'     => 1,
            ],
        ];

        $serviceMap = [
            'accounting' => [$accounting->id],
            'tax'        => [$tax->id],
            'both'       => [$accounting->id, $tax->id],
        ];

        foreach ($clients as $data) {
            $services = $data['services'];
            unset($data['services']);

            /** @var Client $client */
            $client = Client::updateOrCreate(
                ['tin_number' => $data['tin_number']],
                $data
            );

            $client->serviceTypes()->sync($serviceMap[$services]);
        }
    }
}
