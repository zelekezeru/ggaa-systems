<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('task_template_id')->constrained('task_templates');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['Waiting on Client', 'To Do', 'In Review', 'Done'])->default('To Do');
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
