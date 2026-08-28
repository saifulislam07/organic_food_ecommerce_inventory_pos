@extends('admin.layouts.app')

@section('title', $user ? 'Edit User' : 'Add User')
@section('page_title', $user ? 'Edit '.$user->name : 'Add Staff User')

@section('content')
<form action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
    @csrf
    @if($user) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">Account</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email ?? '') }}" autocomplete="off">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mobile</label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $user->mobile ?? '') }}" placeholder="01712345678">
                        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Either email or mobile can be used to sign in.</div>
                    </div>

                    <div>
                        <label class="form-label fw-bold">Password {{ $user ? '' : '*' }}</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password" {{ $user ? '' : 'required' }}>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($user)
                            <div class="form-text">Leave blank to keep the current password.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">Roles</h5>
                </div>
                <div class="card-body p-4">
                    @if($isLastSuperAdmin)
                        <div class="alert alert-warning py-2 small">
                            <i class="bi bi-shield-exclamation"></i>
                            This is the last {{ \App\Support\AdminModules::SUPER_ADMIN }}. That role stays on,
                            otherwise nobody could administer the shop.
                        </div>
                    @endif

                    @forelse($allRoles as $role)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]"
                                   value="{{ $role->name }}" id="role-{{ $role->id }}"
                                   @checked(in_array($role->name, old('roles', $assignedRoles), true))
                                   @disabled($isLastSuperAdmin && $role->name === \App\Support\AdminModules::SUPER_ADMIN)>
                            <label class="form-check-label" for="role-{{ $role->id }}">
                                {{ $role->name }}
                                @if($role->name === \App\Support\AdminModules::SUPER_ADMIN)
                                    <span class="badge bg-danger ms-1">full access</span>
                                @endif
                            </label>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No roles defined yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">Menu Access</h5>
                </div>
                <div class="card-body p-0">
                    <p class="text-muted small px-4 pt-3 mb-0">
                        Granted on top of any role above. Without <strong>view</strong> the menu
                        does not appear in the sidebar at all.
                    </p>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Menu</th>
                                    <th class="text-center">View</th>
                                    <th class="text-center">Create</th>
                                    <th class="text-center">Edit</th>
                                    <th class="text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $module['label'] }}</td>
                                    @foreach(['view', 'create', 'edit', 'delete'] as $ability)
                                        <td class="text-center">
                                            @if(in_array($ability, $module['abilities'], true))
                                                @php $name = $module['key'].'.'.$ability; @endphp
                                                <input class="form-check-input" type="checkbox"
                                                       name="permissions[]" value="{{ $name }}"
                                                       @checked(in_array($name, old('permissions', $assignedPermissions), true))>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> {{ $user ? 'Update User' : 'Create User' }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4">Cancel</a>
    </div>
</form>
@endsection
