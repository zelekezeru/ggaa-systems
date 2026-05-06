<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            // Ethiopian calendar period
            $table->unsignedSmallInteger('eth_year');
            $table->enum('eth_month', [
                'Meskeram', 'Tiqimt', 'Hidar', 'Tahisas',
                'Tirr', 'Yeketit', 'Megabit', 'Miyaziya',
                'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume',
            ]);

            // ── SALES INCOME ──
            $table->decimal('cash_machine_sales', 15, 2)->default(0);
            $table->decimal('manual_sales', 15, 2)->default(0);

            // ── COST OF GOODS SOLD ──
            $table->decimal('beginning_inventory', 15, 2)->default(0);
            $table->decimal('purchases', 15, 2)->default(0);
            $table->decimal('ending_inventory', 15, 2)->default(0);

            // ── ADMINISTRATION EXPENSES ──
            $table->decimal('salary_expense', 15, 2)->default(0);
            $table->decimal('pension_expense', 15, 2)->default(0);
            $table->decimal('printing_expense', 15, 2)->default(0);
            $table->decimal('shed_rent', 15, 2)->default(0);
            $table->decimal('stationery_expense', 15, 2)->default(0);
            $table->decimal('office_rent_expense', 15, 2)->default(0);
            $table->decimal('transport_expense', 15, 2)->default(0);
            $table->decimal('machine_fa_expense', 15, 2)->default(0);
            $table->decimal('eeu_expense', 15, 2)->default(0);
            $table->decimal('maintenance_expense', 15, 2)->default(0);
            $table->decimal('advertising_expense', 15, 2)->default(0);
            $table->decimal('uniform_expense', 15, 2)->default(0);
            $table->decimal('indirect_materials_expense', 15, 2)->default(0);
            $table->decimal('depreciation_expense', 15, 2)->default(0);
            $table->decimal('legal_fee_expense', 15, 2)->default(0);
            $table->decimal('bank_interest_expense', 15, 2)->default(0);
            $table->decimal('bank_service_charge', 15, 2)->default(0);

            // ── VAT ──
            $table->decimal('sales_vat', 15, 2)->default(0);
            $table->decimal('purchase_vat', 15, 2)->default(0);
            $table->decimal('withholding_tax', 15, 2)->default(0);

            // ── WORKFLOW ──
            $table->enum('status', ['draft', 'submitted', 'verified'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('team_project_id')->nullable()->after('client_id')->constrained('team_projects')->nullOnDelete();

            $table->timestamps();

            $table->unique(['client_id', 'eth_year', 'eth_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_ledgers');
    }
};
