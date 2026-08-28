<?php

namespace App\Http\Middleware;

use App\Support\AdminModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derives the required permission from the route name, so a new admin route is
 * covered the moment it is registered instead of waiting for someone to
 * remember a ->middleware('permission:...') call.
 *
 *   admin.products.index   -> products.view
 *   admin.products.create  -> products.create
 *   admin.products.update  -> products.edit
 *   admin.products.destroy -> products.delete
 */
class EnsureAdminPermission
{
    /** Route action segment => ability, for the actions that imply a write. */
    private const WRITE_ACTIONS = [
        'create' => AdminModules::CREATE,
        'store' => AdminModules::CREATE,
        'edit' => AdminModules::EDIT,
        'update' => AdminModules::EDIT,
        'updateStatus' => AdminModules::EDIT,
        'destroy' => AdminModules::DELETE,
        'bulkDestroy' => AdminModules::DELETE,
        'test' => AdminModules::EDIT,
        'read' => null,
        'readAll' => null,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $permission = $this->permissionFor($request);

        if ($permission && ! $request->user()?->can($permission)) {
            abort(403, 'You do not have permission for this section.');
        }

        return $next($request);
    }

    private function permissionFor(Request $request): ?string
    {
        $name = $request->route()?->getName();

        if (! $name || ! str_starts_with($name, 'admin.')) {
            return null;
        }

        $segments = explode('.', substr($name, strlen('admin.')));
        $module = $segments[0];

        // admin.dashboard has no action segment.
        $action = count($segments) > 1 ? end($segments) : 'index';

        // Anything outside the module list — notifications, for instance — is
        // available to everyone who can reach the panel at all.
        if (! array_key_exists($module, AdminModules::MODULES)) {
            return null;
        }

        $ability = $this->abilityFor($action, $request);

        if ($ability === null) {
            return null;
        }

        $available = AdminModules::abilities($module);

        // A module that does not define the derived ability falls back to view,
        // e.g. orders has no create.
        if (! in_array($ability, $available, true)) {
            $ability = AdminModules::VIEW;
        }

        return "{$module}.{$ability}";
    }

    private function abilityFor(string $action, Request $request): ?string
    {
        if (array_key_exists($action, self::WRITE_ACTIONS)) {
            return self::WRITE_ACTIONS[$action];
        }

        // Reading a list or a record only needs view; anything that changes
        // state needs the matching write ability.
        return $request->isMethodSafe() ? AdminModules::VIEW : AdminModules::EDIT;
    }
}
