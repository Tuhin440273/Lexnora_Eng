<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('cefr_level', 2);
            $table->unsignedTinyInteger('difficulty')->default(1); // 1 (easiest) .. 5 (hardest)
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'cefr_level']);
            $table->index(['category_id', 'is_published']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['chapter_id', 'sort_order']);
        });

        // A row is a word, a phrase or a full sentence. Words/phrases that belong
        // to a sentence point to it through parent_id (EASY/MEDIUM mode chunks).
        Schema::create('sentences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('sentences')->cascadeOnDelete();
            $table->string('content_type', 16)->default('sentence');
            $table->text('english_text');
            $table->text('bengali_text')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lesson_id', 'parent_id', 'sort_order']);
            $table->index(['parent_id', 'content_type', 'sort_order']);
        });

        Schema::create('sentence_audio', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32)->default('public');
            $table->string('path');                       // file path only, never binary data
            $table->string('original_name')->nullable();
            $table->string('mime_type', 64)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('voice_label', 60)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sentence_id', 'is_primary']);
        });

        Schema::create('vocabulary', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('term', 150);
            $table->string('normalized_term', 150);
            $table->string('type', 16)->default('word');  // word | phrase
            $table->string('part_of_speech', 30)->nullable();
            $table->text('meaning_en')->nullable();
            $table->text('meaning_bn')->nullable();
            $table->string('pronunciation', 100)->nullable();
            $table->text('example')->nullable();
            $table->string('cefr_level', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['normalized_term', 'type']);
        });

        Schema::create('sentence_vocabulary', function (Blueprint $table) {
            $table->foreignUlid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignUlid('vocabulary_id')->constrained('vocabulary')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->nullable();

            $table->primary(['sentence_id', 'vocabulary_id']);
            $table->index('vocabulary_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentence_vocabulary');
        Schema::dropIfExists('vocabulary');
        Schema::dropIfExists('sentence_audio');
        Schema::dropIfExists('sentences');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('categories');
    }
};
