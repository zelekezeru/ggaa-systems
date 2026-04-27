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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users'); // Can be an Employee or the Client
            $table->text('body');
            $table->string('attachment_path')->nullable(); // For sending invoices, reports, or missing receipts
            $table->boolean('is_read_by_client')->default(false);
            $table->boolean('is_read_by_employee')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
