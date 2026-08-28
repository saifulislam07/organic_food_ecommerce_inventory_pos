@extends('admin.layouts.app')

@section('title', 'Roles')
@section('page_title', 'Roles')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.roles.index'), 'placeholder' => 'Role name'])
</div>
@can('roles.delete')
<form id="bulk-roles" method="POST" action="{{ route('admin.roles.bulkDestroy') }}"
      data-bulk data-bulk-noun="roles">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">Roles</h5>
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Role
            </a>
        @endcan
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        @can('roles.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-roles"></th>@endcan
                        <th class="ps-4">Role</th>
                        <th>Can reach</th>
                        <th class="text-center">Staff</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        @php $isSuper = $role->name === \App\Support\AdminModules::SUPER_ADMIN; @endphp
                        <tr>
                            @can('roles.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-roles" name="ids[]" value="{{ $role->id }}" @disabled($isSuper || $role->users_count)></td>@endcan
                            <td class="ps-4">
                                <span class="fw-bold text-dark">{{ $role->name }}</span>
                                @if($isSuper)
                                    <span class="badge bg-danger ms-1">full access</span>
                                @endif
                            </td>

                            <td>
                                @if($isSuper)
                                    <span class="text-muted small">Everything, including sections added later.</span>
                                @elseif($role->permissions_count)
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $role->permissions_count }} permission(s)
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Nothing yet</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="badge {{ $role->users_count ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">
                                    {{ $role->users_count }}
                                </span>
                            </td>

                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    @can('roles.edit')
                                        <a href="{{ route('admin.roles.edit', $role) }}"
                                           class="btn btn-outline-info" @disabled($isSuper)>
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('roles.delete')
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                                              data-confirm="Remove this role?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger"
                                                    @disabled($isSuper || $role->users_count)>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-person-badge fs-1 d-block mb-2 opacity-25"></i>
                                No roles yet. A role bundles permissions so you can hand the same
                                access to several people at once.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($roles->hasPages())
        <div class="card-footer bg-white">{{ $roles->links() }}</div>
    @endif
</div>

<div class="alert alert-light border mt-4 small mb-0">
    <i class="bi bi-info-circle"></i>
    A role is optional — you can also tick permissions directly on one person in
    <a href="{{ route('admin.users.index') }}">Users</a>. Roles pay off once several
    people need the same access.
</div>
@endsection
