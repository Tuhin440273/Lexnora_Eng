<?php

namespace App\Modules\Admin\Services;

use App\Modules\Courses\Models\Sentence;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Support\Collection;

/**
 * Connects dictionary entries to the sentences that use them. A word matches
 * as a whole word, with a plural ("card" matches "cards", "allergy" matches
 * "allergies"), never inside another word ("change" does not match "exchange").
 */
class VocabularyLinker
{
    public function matches(string $text, string $normalizedTerm): bool
    {
        return $normalizedTerm !== '' && preg_match($this->pattern($normalizedTerm), $text) === 1;
    }

    /**
     * Links an entry to every top-level sentence that contains it. Returns how
     * many new links were made.
     */
    public function linkVocabulary(Vocabulary $vocabulary): int
    {
        if (! $vocabulary->is_active || $vocabulary->normalized_term === '') {
            return 0;
        }

        $created = 0;
        // "allergy" must also find "allergies", so the search uses the part both forms share.
        $needle = preg_match('/[^aeiou]y$/', $vocabulary->normalized_term) === 1
            ? substr($vocabulary->normalized_term, 0, -1)
            : $vocabulary->normalized_term;
        $like = '%'.addcslashes($needle, '%_\\').'%';

        Sentence::query()
            ->whereNull('parent_id')
            ->where('english_text', 'like', $like)
            ->select(['id', 'english_text'])
            ->chunkById(500, function (Collection $sentences) use ($vocabulary, &$created): void {
                $ids = $sentences
                    ->filter(fn (Sentence $sentence) => $this->matches($sentence->english_text, $vocabulary->normalized_term))
                    ->pluck('id')->all();

                $created += count($vocabulary->sentences()->syncWithoutDetaching($ids)['attached']);
            });

        return $created;
    }

    /**
     * Makes the links of one sentence match its current text. Words and
     * phrases that belong to a sentence are not linked.
     */
    public function linkSentence(Sentence $sentence): void
    {
        if ($sentence->parent_id !== null) {
            return;
        }

        $ids = Vocabulary::query()->where('is_active', true)->get(['id', 'normalized_term'])
            ->filter(fn (Vocabulary $entry) => $this->matches($sentence->english_text, $entry->normalized_term))
            ->pluck('id')->all();

        $sentence->vocabulary()->sync($ids);
    }

    private function pattern(string $term): string
    {
        $forms = [preg_quote($term, '/').'(?:s|es)?'];

        if (preg_match('/[^aeiou]y$/', $term) === 1) {
            $forms[] = preg_quote(substr($term, 0, -1), '/').'ies';
        }

        return '/(?<![\p{L}\p{N}])(?:'.implode('|', $forms).')(?![\p{L}\p{N}])/iu';
    }
}
