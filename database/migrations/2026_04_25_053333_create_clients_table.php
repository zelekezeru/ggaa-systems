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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('tin_number')->unique();
            $table->string('email')->nullable();
            $table->string('sector'); // e.g., Manufacturing, Service, Coffee
            $table->enum('service_type', ['Accounting', 'Tax', 'Both']);
            $table->string('logo_path')->nullable()->after('email');
        
            // Crucial relationships
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('users');

            $table->enum('status', ['Active', 'Risk', 'Incomplete'])->default('Incomplete');
            $table->integer('complexity_score')->default(1);
            $table->decimal('retainer_fee', 15, 2)->default(0)->after('complexity_score');
            $table->date('last_payment_date')->nullable()->after('retainer_fee');
            $table->string('payment_status')->default('Pending')->after('last_payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
