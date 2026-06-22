<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('eth_month', 20);
            $table->unsignedSmallInteger('eth_year');

            // Supplier / invoice metadata
            $table->string('supplier_name', 150)->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('invoice_number', 60)->nullable();
            $table->string('description', 255)->nullable();

            // Expense classification — mirrors MonthlyLedger expense columns
            $table->enum('expense_category', [
                'raw_material',
                'detergent',
                'stationery',
                'advertising',
                'maintenance',
                'telephone',
                'salary',
                'pension',
                'transport',
                'office_rent',
                'shed_rent',
                'printing',
                'machine_fa',
                'eeu',
                'uniform',
                'indirect_materials',
                'depreciation',
                'legal_fee',
                'bank_interest',
                'bank_service_charge',
                'other',
            ])->default('other');

            $table->decimal('amount_before_vat', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);

            // Whether this is a VAT-registered invoice (for VAT report)
            $table->boolean('has_vat_receipt')->default(false);

            // Scanned image / document
            $table->string('image_path', 500)->nullable();

            // Who captured it
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['client_id', 'eth_year', 'eth_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
