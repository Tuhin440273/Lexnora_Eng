<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic: reviewable is a Sentence or a SavedVocabulary (see morph map).
        Schema::create('review_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('reviewable');
            $table->string('status', 16)->default('active');
            // `step` indexes into the configurable interval list. interval_days /
            // repetitions / ease_factor leave room for real spaced repetition later.
            $table->unsignedTinyInteger('step')->default(0);
            $table->unsignedSmallInteger('interval_days')->default(1);
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->decimal('ease_factor', 4, 2)->nullable();
            $table->timestamp('due_at');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reviewable_type', 'reviewable_id'], 'review_schedules_unique_item');
            $table->index(['user_id', 'status', 'due_at']);
        });

        Schema::create('review_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('review_schedule_id')->constrained('review_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('practice_attempt_id')->nullable()->constrained('practice_attempts')->nullOnDelete();
            $table->boolean('is_successful');
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedTinyInteger('previous_step');
            $table->unsignedTinyInteger('new_step');
            $table->timestamp('was_due_at');
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['user_id', 'reviewed_at']);
            $table->index('review_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_history');
        Schema::dropIfExists('review_schedules');
    }
};
