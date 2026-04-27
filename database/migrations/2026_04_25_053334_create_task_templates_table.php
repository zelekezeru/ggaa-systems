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
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'VAT Report', 'Payroll'
            $table->enum('frequency', ['Monthly', 'Quarterly', 'Annually']);
            $table->integer('due_date_offset'); // e.g., 20 means it's due on the 20th of the month
            $table->boolean('requires_document')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
