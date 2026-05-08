<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('monthly_ledger_id')->constrained('monthly_ledgers')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->nullable()->default(0);
            $table->decimal('loan_amount', 15, 2)->nullable()->default(0);
            $table->decimal('lc_margin_release', 15, 2)->nullable()->default(0);
            $table->decimal('transfer_in', 15, 2)->nullable()->default(0);
            $table->decimal('transfer_reversal', 15, 2)->nullable()->default(0);
            $table->timestamps();

            $table->unique(['bank_account_id', 'monthly_ledger_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_balances');
    }
};
