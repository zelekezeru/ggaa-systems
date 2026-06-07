<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $manager = User::where('email', 'manager@ggaa.com')->first();

        // ── Branches (one per region in the registry) ────────────────────────
        $branches = [
            'Sidama'           => Branch::firstOrCreate(['name' => 'Hawassa Branch'],          ['location' => 'Sidama Region, Hawassa',          'manager_id' => $manager?->id, 'is_active' => true]),
            'Central Ethiopia' => Branch::firstOrCreate(['name' => 'Central Ethiopia Branch'], ['location' => 'Central Ethiopia, Durame/Hosaina', 'manager_id' => null,          'is_active' => true]),
            'South Ethiopia'   => Branch::firstOrCreate(['name' => 'South Ethiopia Branch'],   ['location' => 'South Ethiopia, Dila',            'manager_id' => null,          'is_active' => true]),
            'Addis Ababa'      => Branch::firstOrCreate(['name' => 'Addis Ababa Main'],         ['location' => 'Bole Sub-City, Addis Ababa',      'manager_id' => null,          'is_active' => true]),
        ];
        $defaultBranch = $branches['Sidama']; // firm HQ — fallback when region is blank

        $manager?->update(['branch_id' => $branches['Addis Ababa']->id]);

        // ── Client master registry ───────────────────────────────────────────
        // [company, region, main_office, phone, tin, email, bank, account_no, bank_branch]
        // Trailing unknown fields can be omitted (padded to null).
        $registry = [
            ['Tuma Plastic', 'CENTRAL ETHIO', 'HOSAINA', '0925521111', '0054600420', 'tumaplastic@gmail.com', 'CEB', null, 'HOSAINA'],
            ['Tegegn Terefe', 'SIDAMA', 'HAWASSA', '0911732589', '0008400835'],
            ['Mesfin Trading', 'SIDAMA', 'HAWASSA', '0916822506', '0001886285'],
            ['Aman Pharma', 'SIDAMA', 'HAWASSA', '0462104646', '0003735049'],
            ['Bniyam Pharmacy', 'SIDAMA', 'HAWASSA', '0916918490', '0013419298'],
            ['Kanna Medd', 'SIDAMA', 'HAWASSA', '0912253160', '0020532462'],
            ['Daniel Desalegn', 'CENTRAL ETHIO', 'DURAME', '0916875002', '0030211043'],
            ['Temesgen Taddese', 'SIDAMA', 'HAWASSA'],
            ['Yichalal Shoe and Leather', 'SIDAMA', 'HAWASSA', '0916825081', '0021395867'],
            ['Yahiwe Niss', 'SIDAMA', 'HAWASSA', '0911665311', '0059420261'],
            ['Mesfin G/Giyorgis', 'CENTRAL ETHIO', 'DOYOGENA', '0913344099'],
            ['Maika Furniture', 'SIDAMA', 'HAWASSA'],
            ['Fre-Yaikob Minimarket', 'SIDAMA', 'HAWASSA', '0905436488'],
            ['Lab-Teck Pharmacy', 'SIDAMA', 'HAWASSA', '0926542937', '0026374891'],
            ['Mega Pharmacy', 'SIDAMA', 'HAWASSA', '0960914054', '0054649757'],
            ['Aposto Coffee', 'SIDAMA', 'HAWASSA', '0913370376'],
            ['Hidase Coffee', 'CENTRAL ETHIO', 'KACHA BIRA', '0911372889'],
            ['Selam Arc and Engineering', 'SIDAMA', 'HAWASSA', '0961047689'],
            ['Atlas Arc and Engineering', 'SIDAMA', 'HAWASSA', '0912250908', '0055173198'],
            ['Workneh Dubala', 'CENTRAL ETHIO', 'HOSAINA', '0933163420'],
            ['Dereje Auto Spare', 'SIDAMA', 'HAWASSA', '0930321962', '0001128102'],
            ['Lenja Motel', 'CENTRAL ETHIO', 'HOSAINA'],
            ['Ebala Farm', 'CENTRAL ETHIO', 'KACHA BIRA', '0911530778', '0044810820'],
            ['Semrawit', 'SIDAMA', 'HAWASSA', '0934805721'],
            ['Tekalign Ersumo', 'CENTRAL ETHIO', 'DURAME', '0930279752'],
            ['Nati Almuniem', 'SIDAMA', 'HAWASSA', '0940404074', '0009035177'],
            ['Misgana Buruno', 'SIDAMA', 'HAWASSA', '0912079314'],
            ['Netsanet Achisaw', 'SIDAMA', 'HAWASSA', '0916140280', '0038288102'],
            ['Roha Construction', 'SIDAMA', 'HAWASSA', '0916582274', '0030617717'],
            ['Sin Tax', 'SIDAMA', 'HAWASSA', '0941005818'],
            ['Act College', 'SIDAMA', 'ALELETA CHUKO', '0911961871', '0065957020'],
            ['Ferenje (Biniyam Hintsa)', 'SIDAMA', 'HAWASSA', '0911551328'],
            ['Bety Matemiya', 'SIDAMA', 'HAWASSA', '0921060657'],
            ['Tesfa Birhan', 'SIDAMA', 'HAWASSA', '0911926245', '0042648451'],
            ['Fanu Building', 'SIDAMA', 'HAWASSA', '0939078127'],
            ['Zenabel Trading', 'CENTRAL ETHIO', 'SHINSHCHO', '0911686582', '0061605541'],
            ['Bun EThio Trading', 'SOUTH ETHIOPIA', 'DILA', '0961753468', '0066740087', 'bunethio@gmail.com', null, null, 'DILA BRANCH'],
            ['Luwa Hotel', 'SIDAMA', 'HAWASSA', '0930069344', '0000653055'],
            ['Saba Electronics', 'SIDAMA', 'HAWASSA'],
            ['Dinku Yaikob', 'SIDAMA', 'HAWASSA'],
            ['Tigist Arega', 'SIDAMA', 'HAWASSA'],
            ['Fanuel Minimarket', 'SIDAMA', 'HAWASSA'],
            ['Yohannes Kabiso', 'CENTRAL ETHIO', 'ANGACHA', '0912165311'],
            ['MR Motel Service', 'CENTRAL ETHIO', 'DURAME', '0911885833'],
            ['Eumas Construction', 'SIDAMA', 'HAWASSA'],
            ['Gedewon', 'SIDAMA', 'HAWASSA', '0913123120'],
            ['Hiwot Elias', 'SIDAMA', 'HAWASSA'],
            ['Dereje Yaikob', 'SIDAMA', 'HAWASSA', null, '0051357347'],
            ['Amenti', 'SIDAMA', 'HAWASSA'],
            ['Think Green', 'SIDAMA', 'HAWASSA'],
            ['Yeabsira', 'SIDAMA', 'HAWASSA'],
            ['Solomon Construction', 'SIDAMA', 'HAWASSA'],
            ['Degife and His Family', 'SIDAMA', 'CHUKO'],
            ['Samrawit Construction', 'SIDAMA', 'HAWASSA'],
            ['Yezema', 'SIDAMA', 'HAWASSA'],
            ['Tewabech Tamiru', 'SIDAMA', 'HAWASSA', '0916875002', '0001528622'],
            ['PB Sparkle', 'SIDAMA', 'HAWASSA', '0911386838'],
            ['Dawit Shibesh', 'SIDAMA', 'HAWASSA'],
            ['Ayele Sulto', 'SIDAMA', 'HAWASSA'],
            ['Tsiyon Eshetu', 'SIDAMA', 'HAWASSA'],
            ['Habte Tomas', 'SIDAMA', 'HAWASSA'],
            ['Kijan Trading PLC', 'CENTRAL ETHIO', 'DURAME'],
            ['Senbeto Hendso', 'SIDAMA', 'HAWASSA'],
            ['Yididiya Printing', 'SIDAMA', 'HAWASSA'],
            ['Sherifa Motel', 'CENTRAL ETHIO', 'DURAME', '0913467801'],
            ['Woma Motel', 'CENTRAL ETHIO', 'DURAME', '0930350120'],
            ['Kijan Agri', 'CENTRAL ETHIO', 'SHINSHCHO', '0926081685'],
            ['Eliana PLC', 'SIDAMA', 'HAWASSA', '0930003137'],
            ['LAK PLC', 'CENTRAL ETHIO', 'MIRAB BADEWACHO'],
            ['Elie PLC', 'SIDAMA', 'HAWASSA'],
            ['Gange Construction', 'SIDAMA', 'HAWASSA'],
            ['Ethio Fruit', 'SIDAMA', 'YIRGALEM', '0986560645'],
            ['Kefele Abacho', 'ADISS ABEBE', 'ADISS ABEBE'],
            ['Loza Event', 'SIDAMA', 'HAWASSA'],
            ['Zetseat Consultancy', 'SIDAMA', 'HAWASSA'],
            ['Aschalew Banta', 'CENTRAL ETHIO', 'DURAME'],
            ['Teketel Car Rent', 'SIDAMA', 'HAWASSA'],
            ['Teketel Car Rent', null, null],
            ['Heptagon', 'CENTRAL ETHIO', 'HOSAINA'],
            ['ANA', 'SIDAMA', null],
            ['Canaan General Trading', 'SIDAMA', 'HAWASSA'],
            ['Listra', 'SIDAMA', 'HAWASSA'],
            ['Naol Hospital', 'SIDAMA', 'HAWASSA'],
            ['Famuye', 'CENTRAL ETHIO', 'DURAME'],
            ['Adanech Tumcha', 'SIDAMA', 'HAWASSA'],
            ['Tampi', 'SOUTH ETHIOPIA', 'SHECHA'],
            ['Alemayehu Bejgo', 'CENTRAL ETHIO', 'DURAME'],
            ['Eyob Derbe', 'SIDAMA', 'HAWASSA'],
            ['DK General Trading', 'SIDAMA', 'HAWASSA'],
            ['Muluwerk Masebo', 'CENTRAL ETHIO', 'DURAME'],
            ['Jale Consultancy', 'SIDAMA', 'HAWASSA'],
            ['Debebe Yitagesu', 'SIDAMA', 'HAWASSA'],
            ['Chargeurs', 'SIDAMA', 'HAWASSA'],
            ['Fisum Matemiya', 'SIDAMA', 'HAWASSA'],
            ['Nobel Lemat', 'SIDAMA', 'HAWASSA'],
            ['Mohammed Furniture', 'SIDAMA', 'HAWASSA'],
            ['Mesk Trading', 'SIDAMA', 'HAWASSA'],
        ];

        $defaultClient = null;

        foreach ($registry as $i => $row) {
            [$name, $region, $office, $phone, $tin, $email, $bank, $account, $bankBranch] = array_pad($row, 9, null);

            $regionName = $this->normalizeRegion($region);
            $branch     = $branches[$regionName] ?? $defaultBranch;
            $hasTin     = ! empty($tin);

            // tin_number is unique + required → synthesise a placeholder when blank.
            $tinKey = $hasTin ? $tin : 'TIN-PENDING-' . ($i + 1);

            /** @var Client $client */
            $client = Client::updateOrCreate(
                ['tin_number' => $tinKey],
                [
                    'company_name'        => $name,
                    'region'              => $regionName,
                    'main_office'         => $this->titleCase($office),
                    'phone'               => $phone,
                    'email'               => $email,
                    'bank_name'           => $bank,
                    'bank_account_number' => $account,
                    'bank_branch'         => $bankBranch,
                    'sector'              => $this->inferSector($name),
                    'branch_id'           => $branch->id,
                    'assigned_employee_id'=> null,
                    'status'              => $hasTin ? 'Active' : 'Incomplete',
                    'complexity_score'    => 1,
                ]
            );

            // Default every client to full-service (Accounting + Tax).
            $client->serviceTypes()->sync([$accounting->id, $tax->id]);

            $defaultClient ??= $client;
        }

        // ── Portal demo user, linked to the first client ─────────────────────
        /** @var User $clientPortalUser */
        $clientPortalUser = User::firstOrCreate(
            ['email' => 'client@ggaa.com'],
            ['name' => 'Portal Client', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        if (! $clientPortalUser->hasRole('Client')) {
            $clientPortalUser->assignRole('Client');
        }

        if ($defaultClient && $clientPortalUser->client_id !== $defaultClient->id) {
            $clientPortalUser->update(['client_id' => $defaultClient->id]);
        }
    }

    /** Map the registry's region label to a canonical name (keys the branch map). */
    private function normalizeRegion(?string $region): ?string
    {
        $r = strtoupper(trim((string) $region));
        if ($r === '') return null;
        if (str_starts_with($r, 'SIDAMA'))  return 'Sidama';
        if (str_starts_with($r, 'CENTRAL')) return 'Central Ethiopia';
        if (str_starts_with($r, 'SOUTH'))   return 'South Ethiopia';
        if (str_starts_with($r, 'AD'))      return 'Addis Ababa';
        return $this->titleCase($r);
    }

    private function titleCase(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : ucwords(strtolower($value));
    }

    /** Best-effort sector from the company name; falls back to "General". */
    private function inferSector(string $name): string
    {
        $n = strtolower($name);
        $map = [
            'pharmac'      => 'Pharmacy',
            'pharma'       => 'Pharmacy',
            'medd'         => 'Pharmacy',
            'hospital'     => 'Healthcare',
            'coffee'       => 'Coffee',
            'fruit'        => 'Agriculture',
            'farm'         => 'Agriculture',
            'agri'         => 'Agriculture',
            'construction' => 'Construction',
            'building'     => 'Construction',
            'arc and eng'  => 'Engineering',
            'motel'        => 'Hospitality',
            'hotel'        => 'Hospitality',
            'event'        => 'Hospitality',
            'furniture'    => 'Furniture',
            'college'      => 'Education',
            'printing'     => 'Printing',
            'electronics'  => 'Electronics',
            'car rent'     => 'Transport',
            'consultanc'   => 'Consultancy',
            'plastic'      => 'Manufacturing',
            'minimarket'   => 'Retail',
            'auto spare'   => 'Automotive',
            'trading'      => 'Trade',
            'trade'        => 'Trade',
        ];
        foreach ($map as $needle => $sector) {
            if (str_contains($n, $needle)) {
                return $sector;
            }
        }
        return 'General';
    }
}
