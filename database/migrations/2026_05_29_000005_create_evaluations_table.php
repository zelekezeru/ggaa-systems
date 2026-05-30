<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single periodic evaluation of one staff member (one per month/year).
 * Holds the overall weighted score (out of 100) plus workflow metadata so
 * every score is attributable to an evaluator and a point in time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_evaluations', function (Blueprint $table) {
            $table->id();
            // Explicit FK names (avoid colliding with an orphaned dictionary entry
            // left by an interrupted DDL on a prior attempt).
            $table->unsignedBigInteger('user_id');       // staff evaluated
            $table->unsignedBigInteger('evaluator_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->foreign('user_id', 'eval_staff_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('evaluator_id', 'eval_evaluator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id', 'eval_branch_fk')->references('id')->on('branches')->nullOnDelete();

            $table->unsignedTinyInteger('period_month'); // 1-12
            $table->unsignedSmallInteger('period_year');

            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->decimal('overall_score', 6, 2)->nullable(); // 0-100, set on finalize
            $table->decimal('total_weight', 8, 2)->nullable();  // sum of weights used
            $table->text('summary_note')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_month', 'period_year'], 'evaluation_period_unique');
            $table->index(['status', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_evaluations');
    }
};
