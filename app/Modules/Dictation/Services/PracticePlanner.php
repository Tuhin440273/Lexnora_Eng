<?php

namespace App\Modules\Dictation\Services;

use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Support\TextStats;

class PracticePlanner
{
    /**
     * The ordered list of sentence ids a session will go through.
     *
     * EASY:   words, then phrases, then the full sentence (shortest first).
     * MEDIUM: phrases, then the full sentence.
     * HARD:   full sentences only.
     *
     * A sentence without words or phrases is simply practised as a whole.
     *
     * @return array<int, string>
     */
    public function plan(Lesson $lesson, PracticeMode $mode): array
    {
        $plan = [];

        foreach ($lesson->sentences()->with('children')->get() as $sentence) {
            if ($mode !== PracticeMode::Hard) {
                foreach ($this->chunks($sentence, $mode) as $chunk) {
                    $plan[] = $chunk->id;
                }
            }

            $plan[] = $sentence->id;
        }

        return $plan;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Sentence>
     */
    private function chunks(Sentence $sentence, PracticeMode $mode)
    {
        $types = $mode === PracticeMode::Easy
            ? [ContentType::Word, ContentType::Phrase]
            : [ContentType::Phrase];

        return $sentence->children
            ->filter(fn (Sentence $child) => in_array($child->content_type, $types, true))
            ->sortBy([
                fn (Sentence $a, Sentence $b) => $a->content_type->stage() <=> $b->content_type->stage(),
                fn (Sentence $a, Sentence $b) => TextStats::wordCount($a->english_text) <=> TextStats::wordCount($b->english_text),
                fn (Sentence $a, Sentence $b) => $a->sort_order <=> $b->sort_order,
            ])
            ->values();
    }
}
