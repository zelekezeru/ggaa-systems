<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['team_project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_project_messages');
    }
};
