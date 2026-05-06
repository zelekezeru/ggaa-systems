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
        Schema::table('service_invoice_payments', function (Blueprint $table) {
            $table->string('payment_method')->change();
            $table->string('receipt_link')->nullable()->after('reference');
            $table->string('receipt_photo_path')->nullable()->after('receipt_link');
            $table->string('status')->default('Completed')->after('receipt_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_invoice_payments', function (Blueprint $table) {
            $table->dropColumn(['receipt_link', 'receipt_photo_path', 'status']);
        });
    }
};
