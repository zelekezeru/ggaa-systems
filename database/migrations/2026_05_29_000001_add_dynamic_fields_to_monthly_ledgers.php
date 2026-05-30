<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            // Dynamic profit tax rate (default Ethiopian top bracket = 35%)
            $table->decimal('tax_rate', 5, 2)->default(35.00)->after('notes');

            // Client-specific extra expense line items (JSON array of {label, amount})
            $table->json('custom_expenses')->nullable()->after('tax_rate');

            // Standard fields hidden for this client/entry (JSON array of field keys)
            $table->json('hidden_expense_fields')->nullable()->after('custom_expenses');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_ledgers', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'custom_expenses', 'hidden_expense_fields']);
        });
    }
};
