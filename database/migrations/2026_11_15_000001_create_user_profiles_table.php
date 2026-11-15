<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 60)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('native_language', 8)->default('bn');
            $table->string('target_cefr_level', 2)->nullable();
            $table->unsignedSmallInteger('daily_goal_minutes')->default(10);
            $table->unsignedSmallInteger('daily_goal_sentences')->default(10);
            // Display timezone only. All stored timestamps stay in UTC.
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('leaderboard_anonymous')->default(false);
            // UI preferences: default playback speed, default practice mode, etc.
            $table->json('preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
