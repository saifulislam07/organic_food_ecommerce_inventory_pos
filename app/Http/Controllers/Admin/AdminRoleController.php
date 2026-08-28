<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Support\AdminModules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Named bundles of permissions, so "Cashier" is defined once instead of being
 * ticked out by hand for every member of staff.
 */
class AdminRoleController extends Controller
{
    use BulkDeletes;
    use SearchesRecords;

    public function index(Request $request)
    {
        return view('admin.roles.index', [
            'roles' => $this->applySearch(
                Role::withCount('users', 'permissions'),
                $request->input('search'),
                ['name']
            )->orderBy('name')->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.roles.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role)
    {
        abort_if($this->isSuperAdmin($role), 403, 'The Super Admin role always has full access and cannot be edited.');

        return view('admin.roles.edit', $this->formData($role));
    }

    public function update(Request $request, Role $role)
    {
        abort_if($this->isSuperAdmin($role), 403);

        $validated = $request->validate($this->rules($role));

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role)
    {
        if ($this->isSuperAdmin($role)) {
            return back()->withErrors(['role' => 'The Super Admin role cannot be removed.']);
        }

        // Deleting a role in use would silently strip those people's access.
        if ($role->users()->exists()) {
            $count = $role->users()->count();

            return back()->withErrors([
                'role' => "\"{$role->name}\" is held by {$count} user(s). Reassign them first.",
            ]);
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$name}\" removed.");
    }

    private function rules(?Role $role = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'name')->ignore($role?->id),
                Rule::notIn([AdminModules::SUPER_ADMIN]),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(AdminModules::permissions())],
        ];
    }

    private function formData(?Role $role = null): array
    {
        return [
            'role' => $role,
            'modules' => AdminModules::grid(),
            'assigned' => $role ? $role->permissions->pluck('name')->all() : [],
        ];
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Role::class, fn (Role $role) => match (true) {
            $this->isSuperAdmin($role) => 'The Super Admin role cannot be removed.',
            $role->users()->exists() => "\"{$role->name}\" is still held by staff.",
            default => null,
        });

        return $this->bulkResponse($result, 'roles', 'admin.roles.index');
    }

    private function isSuperAdmin(Role $role): bool
    {
        return $role->name === AdminModules::SUPER_ADMIN;
    }
}
