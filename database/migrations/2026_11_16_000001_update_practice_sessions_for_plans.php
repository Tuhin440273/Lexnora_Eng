<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            // lesson = a normal lesson run, review = due items, mistakes = "practice again"
            $table->string('kind', 12)->default('lesson');
            // Ordered sentence ids, fixed when the session starts.
            $table->json('plan')->nullable();
        });

        // Review and mistake sessions mix sentences from many lessons.
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->char('lesson_id', 26)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropColumn(['kind', 'plan']);
        });
    }
};
