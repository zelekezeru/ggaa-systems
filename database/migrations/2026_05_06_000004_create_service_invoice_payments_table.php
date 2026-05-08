<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->string('receipt_link')->nullable();
            $table->string('receipt_photo_path')->nullable();
            $table->string('status')->default('Completed');
            $table->date('paid_at');
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index('service_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_invoice_payments');
    }
};
