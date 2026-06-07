<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Registry location (from the client master sheet)
            $table->string('region')->nullable()->after('company_name');
            $table->string('main_office')->nullable()->after('region');

            // Client login credential (e.g. e-Trade / Gmail). Stored encrypted.
            $table->text('email_password')->nullable()->after('email')->comment('Stored encrypted');

            // Primary bank details. account number is a STRING to preserve long
            // numbers / leading zeros (Excel mangles these into scientific notation).
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'region', 'main_office', 'email_password',
                'bank_name', 'bank_account_number', 'bank_branch',
            ]);
        });
    }
};
