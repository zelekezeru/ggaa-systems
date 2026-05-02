<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // e.g. 'first_task_done', 'on_time_streak_10'
            $table->string('name');
            $table->string('description');
            $table->string('icon')->default('🏆');     // emoji or icon key
            $table->string('tier')->default('bronze'); // bronze | silver | gold | platinum
            $table->integer('points')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
