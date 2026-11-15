<?php

namespace App\Modules\Admin\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SortOrderService
{
    /**
     * The next free position inside a group of siblings.
     *
     * @param  Builder<Model>  $siblings
     */
    public function next(Builder $siblings): int
    {
        return ((int) $siblings->max('sort_order')) + 1;
    }

    /**
     * Moves a row one place up or down among its siblings. Positions are
     * renumbered 1..n first, so gaps and duplicates never cause trouble.
     *
     * @param  Builder<Model>  $siblings  Query that returns the model and all its siblings.
     */
    public function move(Model $model, Builder $siblings, string $direction): void
    {
        DB::transaction(function () use ($model, $siblings, $direction): void {
            $ids = $siblings->clone()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->values()
                ->all();

            $index = array_search($model->getKey(), $ids, true);

            if ($index === false) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                $siblings->clone()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
