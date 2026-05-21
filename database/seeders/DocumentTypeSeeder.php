<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Box Files', 'description' => 'Large file binders for corporate records.'],
            ['name' => 'Receipts', 'description' => 'Sales, purchases, and expense receipts.'],
            ['name' => 'Pads', 'description' => 'Accounting notepad blocks or receipt pads.'],
            ['name' => 'Letters', 'description' => 'Official corporate or governmental compliance letters.'],
            ['name' => 'Others', 'description' => 'Uncategorized or miscellaneous documents.'],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
