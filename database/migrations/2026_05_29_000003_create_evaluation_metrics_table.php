<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue of evaluation metrics. A metric is a single, weighted,
 * scoreable dimension of staff performance (e.g. "On-time Task Delivery",
 * "Ledger Entry Accuracy", "Professional Ethics & Integrity").
 *
 * Metrics can be `auto` (computed from system data) or `manual` (scored by a
 * supervisor), and may target specific roles so junior accountants, finance
 * staff and managers each get a relevant rubric.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // High-level bucket the metric belongs to (for grouping & reporting)
            $table->string('category')->default('custom');

            // 'auto'   → score derived by EvaluationService from system data
            // 'manual' → score entered by the evaluator
            $table->enum('source', ['auto', 'manual'])->default('manual');

            // For auto metrics: which calculator to run (see EvaluationService)
            $table->string('computation_key')->nullable();

            // Roles this metric is relevant to. NULL = applies to every staff role.
            $table->json('applies_to_roles')->nullable();

            // Suggested weight (%). Per-staff weight overrides live in the pivot.
            $table->decimal('default_weight', 6, 2)->default(10);

            $table->decimal('max_score', 6, 2)->default(100);

            // System metrics ship with the app and can be deactivated but not deleted.
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_metrics');
    }
};
