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
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            // We already have status, but let's make sure it defaults to 'Draft' or similar if we change workflow
            // $table->string('status')->default('Draft')->change(); // If we wanted to change default
        });
    }

    public function down(): void
    {
        Schema::table('service_invoice_payments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'scheduled_at']);
        });
    }
};
