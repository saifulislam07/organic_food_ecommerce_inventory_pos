<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Click-to-sort column headers for the admin tables.
 *
 * The map is a whitelist: the ?sort= key a visitor sends is only ever looked up
 * in it, never spliced into SQL, so an unknown key falls back to the default
 * instead of reaching the database. A key may point at one column or several
 * ("investor" => ['sort_order', 'name']).
 *
 * The resolved state is shared with the views as $adminSort so
 * admin.partials.sort can draw the arrow on the right column without every
 * controller having to pass it through.
 */
trait SortsRecords
{
    /**
     * @param  array<string, string|list<string>>  $sortable  public key => column(s)
     * @param  string  $default  key to use when none was asked for
     * @param  string  $defaultDir  direction for the untouched list — 'asc' for a
     *                              hand-arranged sort_order, 'desc' for newest-first
     */
    protected function applySort(
        Builder $query,
        Request $request,
        array $sortable,
        string $default,
        string $defaultDir = 'desc'
    ): Builder {
        $key = (string) $request->query('sort');

        if (! array_key_exists($key, $sortable)) {
            $key = $default;
        }

        $asked = strtolower((string) $request->query('dir'));
        $direction = in_array($asked, ['asc', 'desc'], true)
            ? $asked
            : (strtolower($defaultDir) === 'asc' ? 'asc' : 'desc');

        View::share('adminSort', ['key' => $key, 'dir' => $direction]);

        $columns = (array) $sortable[$key];

        foreach ($columns as $column) {
            $query->orderBy($column, $direction);
        }

        // Rows that tie on the sort column would otherwise come back in whatever
        // order the database felt like, which reshuffles between page 1 and page
        // 2 and drops records out of the middle. The primary key settles them.
        $model = $query->getModel();

        if (! in_array($model->getKeyName(), $columns, true)) {
            $query->orderBy($model->getQualifiedKeyName(), 'desc');
        }

        return $query;
    }
}
