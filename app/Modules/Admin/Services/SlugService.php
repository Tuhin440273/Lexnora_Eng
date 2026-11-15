<?php

namespace App\Modules\Admin\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Builds a slug that is unique for the given model, counting soft-deleted
     * rows too (the database unique index does).
     *
     * @param  class-string<Model>  $model
     */
    public function unique(string $model, string $source, ?string $ignoreId = null, int $maxLength = 120): string
    {
        $base = Str::slug($source);
        $base = $base === '' ? 'item' : mb_substr($base, 0, max(1, $maxLength - 8));
        $base = rtrim($base, '-');

        $slug = $base;
        $suffix = 2;

        while ($this->exists($model, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function exists(string $model, string $slug, ?string $ignoreId): bool
    {
        return $model::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
