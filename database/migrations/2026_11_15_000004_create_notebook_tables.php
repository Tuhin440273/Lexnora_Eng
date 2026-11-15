<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_vocabulary', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('vocabulary_id')->nullable()->constrained('vocabulary')->nullOnDelete();
            $table->foreignUlid('context_sentence_id')->nullable()->constrained('sentences')->nullOnDelete();
            $table->string('term', 150);
            $table->string('normalized_term', 150);
            $table->string('type', 16)->default('word');
            $table->text('context_text')->nullable();        // snapshot of the original sentence
            $table->text('personal_meaning')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_learned')->default(false);
            $table->timestamp('learned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_learned']);
            $table->index(['user_id', 'normalized_term']);
        });

        // One row per (user, sentence). Details of the last wrong answer come from
        // the linked attempt, so nothing is duplicated.
        Schema::create('mistake_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignUlid('latest_attempt_id')->nullable()->constrained('practice_attempts')->nullOnDelete();
            $table->unsignedSmallInteger('times_missed')->default(1);
            $table->timestamp('last_missed_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'sentence_id']);
            $table->index(['user_id', 'resolved_at']);
        });

        Schema::create('user_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sentence_id')->nullable()->constrained('sentences')->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'sentence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notes');
        Schema::dropIfExists('mistake_records');
        Schema::dropIfExists('saved_vocabulary');
    }
};
