<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_leader_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', [
                'planning',
                'in_progress',
                'in_review',
                'completed',
                'cancelled',
            ])->default('planning');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('start_date')->nullable();
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('complexity_score')->default(3);
            $table->timestamps();

            $table->index(['status', 'branch_id']);
            $table->index(['team_leader_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_projects');
    }
};
