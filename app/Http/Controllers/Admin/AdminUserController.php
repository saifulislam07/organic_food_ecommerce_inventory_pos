<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    use BulkDeletes;
    use SearchesRecords;

    public function index(Request $request)
    {
        $users = $this->applySearch(
            User::where('role', 'admin')->with('roles'),
            $request->input('search'),
            ['name', 'email', 'mobile']
        )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $user = User::create([
            'name' => $validated['name'],
            'email' => ($validated['email'] ?? null) ?: null,
            'mobile' => ($validated['mobile'] ?? null) ?: null,
            'password' => Hash::make($validated['password']),
            // Staff need this to pass the is_admin gate; permissions decide the rest.
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->applyAccess($request, $user);

        return redirect()->route('admin.users.index')->with('success', "{$user->name} can now sign in.");
    }

    public function edit(User $user)
    {
        abort_unless($user->role === 'admin', 404);

        return view('admin.users.edit', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'admin', 404);

        $validated = $request->validate($this->rules($user));

        $user->update([
            'name' => $validated['name'],
            'email' => ($validated['email'] ?? null) ?: null,
            'mobile' => ($validated['mobile'] ?? null) ?: null,
        ]);

        // Blank means "leave the current password alone".
        if (filled($validated['password'] ?? null)) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $this->applyAccess($request, $user, $this->isLastSuperAdmin($user));

        return redirect()->route('admin.users.index')->with('success', "{$user->name} updated.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->role === 'admin', 404);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($this->isLastSuperAdmin($user)) {
            return back()->withErrors(['user' => 'This is the last super admin — promote someone else first.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "{$name} removed.");
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, User::class, fn (User $user) => match (true) {
            $user->role !== 'admin' => "{$user->name} is not a staff account.",
            $user->is($request->user()) => 'You cannot delete your own account.',
            $this->isLastSuperAdmin($user) => "{$user->name} is the last super admin.",
            default => null,
        });

        return $this->bulkResponse($result, 'users', 'admin.users.index');
    }

    /* ------------------------------------------------------------ helpers */

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(AdminModules::permissions())],
        ];
    }

    private function formData(?User $user = null): array
    {
        return [
            'user' => $user,
            'allRoles' => Role::orderBy('name')->get(),
            'modules' => AdminModules::grid(),
            'assignedRoles' => $user ? $user->roles->pluck('name')->all() : [],
            'assignedPermissions' => $user ? $user->getDirectPermissions()->pluck('name')->all() : [],
            'isLastSuperAdmin' => $user ? $this->isLastSuperAdmin($user) : false,
        ];
    }

    private function applyAccess(Request $request, User $user, bool $keepSuperAdmin = false): void
    {
        $roles = $request->input('roles', []);

        if ($keepSuperAdmin && ! in_array(AdminModules::SUPER_ADMIN, $roles, true)) {
            $roles[] = AdminModules::SUPER_ADMIN;
        }

        $user->syncRoles($roles);
        $user->syncPermissions($request->input('permissions', []));
    }

    /** Removing the last unrestricted account would lock everyone out. */
    private function isLastSuperAdmin(User $user): bool
    {
        if (! $user->hasRole(AdminModules::SUPER_ADMIN)) {
            return false;
        }

        return User::role(AdminModules::SUPER_ADMIN)->count() <= 1;
    }
}
