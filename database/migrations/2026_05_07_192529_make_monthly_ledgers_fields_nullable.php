<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            $table->decimal('cash_machine_sales', 15, 2)->nullable()->change();
            $table->decimal('manual_sales', 15, 2)->nullable()->change();
            $table->decimal('beginning_inventory', 15, 2)->nullable()->change();
            $table->decimal('purchases', 15, 2)->nullable()->change();
            $table->decimal('ending_inventory', 15, 2)->nullable()->change();
            $table->decimal('salary_expense', 15, 2)->nullable()->change();
            $table->decimal('pension_expense', 15, 2)->nullable()->change();
            $table->decimal('printing_expense', 15, 2)->nullable()->change();
            $table->decimal('shed_rent', 15, 2)->nullable()->change();
            $table->decimal('stationery_expense', 15, 2)->nullable()->change();
            $table->decimal('office_rent_expense', 15, 2)->nullable()->change();
            $table->decimal('transport_expense', 15, 2)->nullable()->change();
            $table->decimal('machine_fa_expense', 15, 2)->nullable()->change();
            $table->decimal('eeu_expense', 15, 2)->nullable()->change();
            $table->decimal('maintenance_expense', 15, 2)->nullable()->change();
            $table->decimal('advertising_expense', 15, 2)->nullable()->change();
            $table->decimal('uniform_expense', 15, 2)->nullable()->change();
            $table->decimal('indirect_materials_expense', 15, 2)->nullable()->change();
            $table->decimal('depreciation_expense', 15, 2)->nullable()->change();
            $table->decimal('legal_fee_expense', 15, 2)->nullable()->change();
            $table->decimal('bank_interest_expense', 15, 2)->nullable()->change();
            $table->decimal('bank_service_charge', 15, 2)->nullable()->change();
            $table->decimal('sales_vat', 15, 2)->nullable()->change();
            $table->decimal('purchase_vat', 15, 2)->nullable()->change();
            $table->decimal('withholding_tax', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            //
        });
    }
};
