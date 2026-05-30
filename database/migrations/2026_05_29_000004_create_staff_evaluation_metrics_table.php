<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-staff assignment of evaluation metrics with an individual weight.
 *
 * This is what makes the rubric dynamic: a manager can attach extra metrics
 * to a specific staff member (or detach irrelevant ones) and tune the weight
 * so the rubric fits that person's actual responsibilities. Weights are
 * normalized to 100% at scoring time, so they need not sum exactly to 100.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluation_metric_id')->constrained('evaluation_metrics')->cascadeOnDelete();
            $table->decimal('weight', 6, 2)->default(10);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'evaluation_metric_id'], 'staff_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_evaluation_metrics');
    }
};
