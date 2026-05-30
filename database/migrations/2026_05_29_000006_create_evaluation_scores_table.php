<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One line item per metric within an evaluation — the traceability backbone.
 *
 * Snapshots the metric name, category, weight and max_score at evaluation time
 * (so later catalogue edits never rewrite history), records the raw score, the
 * normalized 0-100 value, the weighted contribution to the overall score, who
 * scored it and when, plus a justification note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->foreign('evaluation_id', 'evalscore_eval_fk')->references('id')->on('staff_evaluations')->cascadeOnDelete();
            $table->foreignId('evaluation_metric_id')->nullable()->constrained('evaluation_metrics')->nullOnDelete();

            // Snapshots (immutable history)
            $table->string('metric_name');
            $table->string('category')->default('custom');
            $table->enum('source', ['auto', 'manual'])->default('manual');
            $table->decimal('weight', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(100);

            // Scoring
            $table->decimal('raw_score', 6, 2)->nullable();        // 0..max_score
            $table->decimal('normalized_score', 6, 2)->nullable(); // raw/max * 100
            $table->decimal('weighted_score', 6, 2)->nullable();   // contribution to overall

            $table->boolean('is_auto')->default(false);
            $table->text('justification')->nullable();

            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->index('evaluation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};
