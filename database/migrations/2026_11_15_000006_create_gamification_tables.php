<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_checkins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('checkin_date');                    // date in the user's timezone
            $table->unsignedSmallInteger('streak_after')->default(1);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'checkin_date']);
        });

        // Append-only ledger. A user's XP is the SUM of this table.
        Schema::create('user_xp_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('action', 40);                    // dictation_correct, checkin, badge ...
            $table->string('source_type', 40)->nullable();
            $table->string('source_id', 26)->nullable();
            // Same key twice = same reward twice, so the unique index blocks it.
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('description')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->index(['user_id', 'awarded_at']);
            $table->index(['awarded_at', 'user_id']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 60)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 60)->nullable();          // lucide-react icon name
            $table->string('criteria_type', 40);             // xp_total | streak_days | lessons_completed | sessions_completed
            $table->unsignedInteger('criteria_value');
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['criteria_type', 'criteria_value']);
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();

            $table->primary(['user_id', 'badge_id']);
        });

        // Aggregates for the dashboard / charts. Rebuildable from the raw tables.
        Schema::create('daily_learning_statistics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('study_seconds')->default(0);
            $table->unsignedSmallInteger('sessions_count')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_attempts')->default(0);
            $table->decimal('accuracy_total', 10, 2)->default(0);   // sum; average = total / attempts
            $table->unsignedSmallInteger('lessons_completed')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'stat_date']);
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_learning_statistics');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_xp_transactions');
        Schema::dropIfExists('learning_checkins');
    }
};
