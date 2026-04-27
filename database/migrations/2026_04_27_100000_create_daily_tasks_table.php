<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                'mail_delivery',
                'client_visit',
                'tax_commission',
                'errand',
                'internal_meeting',
                'other',
            ])->default('other');
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_tasks');
    }
};
