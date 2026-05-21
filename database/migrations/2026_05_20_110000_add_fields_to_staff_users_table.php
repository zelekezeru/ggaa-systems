<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->integer('max_capacity')->nullable()->after('employment_type')->comment('Dynamic capacity limit. Falls back to config default.');
            $table->text('academic_experience')->nullable()->after('max_capacity')->comment('Academic background details.');
            $table->text('training_experience')->nullable()->after('academic_experience')->comment('Professional training details.');
            $table->text('performance_experience')->nullable()->after('training_experience')->comment('Past performance history.');
        });
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->dropColumn(['max_capacity', 'academic_experience', 'training_experience', 'performance_experience']);
        });
    }
};
