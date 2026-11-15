<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('mode', 8);                         // easy | medium | hard
            $table->string('status', 16)->default('in_progress');
            $table->unsignedSmallInteger('total_items')->default(0);
            $table->unsignedSmallInteger('completed_items')->default(0);
            $table->unsignedSmallInteger('correct_items')->default(0);
            $table->decimal('average_accuracy', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);   // computed server-side
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'status']);
            $table->index(['lesson_id', 'user_id']);
        });

        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('practice_session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->text('submitted_text');
            $table->text('expected_text');                      // snapshot at the time of the attempt
            $table->decimal('accuracy', 5, 2);
            $table->boolean('is_correct');
            $table->json('error_details')->nullable();          // diff operations + counts
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->unsignedSmallInteger('replay_count')->default(0);
            $table->decimal('playback_speed', 3, 2)->nullable();
            $table->unsignedInteger('time_spent_ms')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->index(['practice_session_id', 'sentence_id']);
            $table->index(['user_id', 'sentence_id']);
            $table->index(['user_id', 'answered_at']);
        });

        Schema::create('user_course_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('status', 16)->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('status', 16)->default('not_started');
            $table->string('last_mode', 8)->nullable();
            $table->decimal('best_accuracy', 5, 2)->nullable();
            $table->unsignedSmallInteger('sessions_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lesson_progress');
        Schema::dropIfExists('user_course_progress');
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('practice_sessions');
    }
};
