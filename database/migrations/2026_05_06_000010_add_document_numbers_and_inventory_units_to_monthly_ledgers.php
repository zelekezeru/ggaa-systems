<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            // Cash machine receipt audit trail
            $table->unsignedBigInteger('cash_machine_start_number')->nullable()->after('cash_machine_sales');
            $table->unsignedBigInteger('cash_machine_end_number')->nullable()->after('cash_machine_start_number');

            // Manual receipt audit trail
            $table->unsignedBigInteger('manual_receipt_start_number')->nullable()->after('manual_sales');
            $table->unsignedBigInteger('manual_receipt_end_number')->nullable()->after('manual_receipt_start_number');

            // Inventory unit-level tracking (decimal so it works for kg / litres / etc. as well as integer counts)
            $table->decimal('inventory_items_start',    14, 3)->nullable()->after('ending_inventory');
            $table->decimal('inventory_items_end',      14, 3)->nullable()->after('inventory_items_start');
            $table->decimal('inventory_sold_quantity',  14, 3)->nullable()->after('inventory_items_end');

            // Helpful indexes for the cross-month overlap check
            $table->index(['client_id', 'cash_machine_start_number', 'cash_machine_end_number'], 'ml_cm_range_idx');
            $table->index(['client_id', 'manual_receipt_start_number', 'manual_receipt_end_number'], 'ml_mr_range_idx');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            $table->dropIndex('ml_cm_range_idx');
            $table->dropIndex('ml_mr_range_idx');
            $table->dropColumn([
                'cash_machine_start_number',
                'cash_machine_end_number',
                'manual_receipt_start_number',
                'manual_receipt_end_number',
                'inventory_items_start',
                'inventory_items_end',
                'inventory_sold_quantity',
            ]);
        });
    }
};
