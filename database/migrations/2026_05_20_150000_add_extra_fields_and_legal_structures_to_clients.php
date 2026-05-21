<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create legal structures table
        Schema::create('legal_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default legal structures
        DB::table('legal_structures')->insert([
            ['name' => 'Sole Proprietorship', 'description' => 'Owned and run by one individual.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PLC (Private Limited Company)', 'description' => 'Private business entity.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Share Company', 'description' => 'Public limited company with share structures.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Partnership', 'description' => 'Owned by two or more individuals.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Add new columns to clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->string('trade_license_number')->nullable()->after('tin_number');
            $table->text('address')->nullable()->after('trade_license_number');
            $table->string('tax_center')->nullable()->after('address');
            $table->foreignId('legal_structure_id')->nullable()->after('tax_center')->constrained('legal_structures');
            $table->string('owner_name')->nullable()->after('legal_structure_id');
            $table->string('phone')->nullable()->after('owner_name');
            $table->string('etrade_email')->nullable()->after('phone');
            $table->text('etrade_password')->nullable()->after('etrade_email')->comment('Encrypted securely');
            $table->string('venture')->nullable()->after('etrade_password');
            $table->integer('year_established')->nullable()->after('venture');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['legal_structure_id']);
            $table->dropColumn([
                'trade_license_number',
                'address',
                'tax_center',
                'legal_structure_id',
                'owner_name',
                'phone',
                'etrade_email',
                'etrade_password',
                'venture',
                'year_established'
            ]);
        });

        Schema::dropIfExists('legal_structures');
    }
};
