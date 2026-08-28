<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * One search box behaves the same on every admin table.
 *
 * Columns may be plain ("name") or reach through a relation
 * ("productVariant.product.name"); everything is OR'd together inside a single
 * group so it cannot escape any filters already on the query.
 */
trait SearchesRecords
{
    protected function applySearch(Builder $query, ?string $term, array $columns): Builder
    {
        $term = trim((string) $term);

        if ($term === '' || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $group) use ($term, $columns) {
            foreach ($columns as $column) {
                if (! str_contains($column, '.')) {
                    $group->orWhere($column, 'like', "%{$term}%");

                    continue;
                }

                // "productVariant.product.name" -> relation path + final column
                $segments = explode('.', $column);
                $field = array_pop($segments);
                $relation = implode('.', $segments);

                $group->orWhereHas(
                    $relation,
                    fn (Builder $related) => $related->where($field, 'like', "%{$term}%")
                );
            }
        });
    }
}
