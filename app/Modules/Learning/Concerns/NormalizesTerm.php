<?php

namespace App\Modules\Learning\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Keeps `normalized_term` (lowercase, single-spaced) in sync with `term`.
 * Used for searching and de-duplication of vocabulary.
 */
trait NormalizesTerm
{
    protected static function bootNormalizesTerm(): void
    {
        static::saving(function (Model $model): void {
            $model->normalized_term = static::normalizeTerm((string) $model->term);
        });
    }

    public static function normalizeTerm(string $term): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $term)));
    }
}
