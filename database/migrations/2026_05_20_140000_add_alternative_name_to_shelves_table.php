<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->string('alternative_name')->nullable()->after('label')->comment('Currently used legacy or physical shelf name');
        });
    }

    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->dropColumn('alternative_name');
        });
    }
};
