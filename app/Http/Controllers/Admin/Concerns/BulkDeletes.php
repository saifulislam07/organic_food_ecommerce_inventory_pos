<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared "delete the ticked rows" behaviour.
 *
 * Rows are deleted one at a time rather than with a mass delete: model hooks
 * are what remove the uploaded files, and a mass delete skips them.
 */
trait BulkDeletes
{
    /**
     * @param  class-string<Model>  $model
     * @param  callable(Model): ?string  $blocker  returns why a row must stay, or null
     */
    protected function bulkDelete(Request $request, string $model, ?callable $blocker = null): array
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return ['deleted' => 0, 'blocked' => []];
        }

        $deleted = 0;
        $blocked = [];

        DB::transaction(function () use ($model, $ids, $blocker, &$deleted, &$blocked) {
            foreach ($model::whereIn('id', $ids)->get() as $record) {
                $reason = $blocker ? $blocker($record) : null;

                if ($reason) {
                    $blocked[] = $reason;

                    continue;
                }

                $record->delete();
                $deleted++;
            }
        });

        return ['deleted' => $deleted, 'blocked' => $blocked];
    }

    /** Turns a bulk result into the flash messages the toaster shows. */
    protected function bulkResponse(array $result, string $noun, string $route)
    {
        $redirect = redirect()->route($route);

        if ($result['deleted'] > 0) {
            $redirect->with('success', "{$result['deleted']} {$noun} deleted.");
        }

        if ($result['blocked']) {
            $redirect->withErrors(['bulk' => $result['blocked']]);
        }

        if ($result['deleted'] === 0 && ! $result['blocked']) {
            $redirect->with('error', 'Nothing was selected.');
        }

        return $redirect;
    }
}
