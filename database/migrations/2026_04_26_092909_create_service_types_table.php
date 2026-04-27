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
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('complexity_weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_service_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('client_service_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'service_type')) {
                $table->dropColumn('service_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_service_type');
        Schema::dropIfExists('client_service_type');
        Schema::dropIfExists('service_types');
        
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'service_type')) {
                $table->enum('service_type', ['Accounting', 'Tax', 'Both'])->default('Accounting');
            }
        });
    }
};
