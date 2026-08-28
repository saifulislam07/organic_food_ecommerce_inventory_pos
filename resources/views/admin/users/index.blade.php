@extends('admin.layouts.app')

@section('title', 'Users & Roles')
@section('page_title', 'Users & Roles')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.users.index'), 'placeholder' => 'Name, email or mobile'])
</div>
@can('users.delete')
<form id="bulk-users" method="POST" action="{{ route('admin.users.bulkDestroy') }}"
      data-bulk data-bulk-noun="users">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">Staff Accounts</h5>
                @can('users.create')
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Add User
                    </a>
                @endcan
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                @can('users.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-users"></th>@endcan
                                <th class="ps-4">Name</th>
                                <th>Contact</th>
                                <th>Access</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $staff)
                            <tr>
                                @can('users.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-users" name="ids[]" value="{{ $staff->id }}" @disabled($staff->is(auth()->user()))></td>@endcan
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">{{ $staff->name }}</span>
                                    @if($staff->is(auth()->user()))
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">you</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $staff->email ?? '—' }}
                                    @if($staff->mobile)<br>{{ $staff->mobile }}@endif
                                </td>
                                <td>
                                    @forelse($staff->roles as $role)
                                        <span class="badge {{ $role->name === \App\Support\AdminModules::SUPER_ADMIN ? 'bg-danger' : 'bg-primary-subtle text-primary' }}">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="badge bg-warning-subtle text-warning">No role — sees nothing</span>
                                    @endforelse
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        @can('users.edit')
                                            <a href="{{ route('admin.users.edit', $staff) }}" class="btn btn-outline-info">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('users.delete')
                                            <form action="{{ route('admin.users.destroy', $staff) }}" method="POST"
                                                  data-confirm="Remove this user?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" @disabled($staff->is(auth()->user()))>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">No staff accounts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($users->hasPages())
                <div class="card-footer bg-white">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">Roles</h5>
            </div>
            <div class="card-body">
                @forelse($roles as $role)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="fw-bold">{{ $role->name }}</span>
                        <span class="badge bg-light text-muted">{{ $role->users_count }} user(s)</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No roles yet.</p>
                @endforelse

                <p class="text-muted small mb-0 mt-3">
                    <strong>{{ \App\Support\AdminModules::SUPER_ADMIN }}</strong> passes every
                    permission check, so it never needs updating when a new section is added.
                </p>

                @can('roles.view')
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                        <i class="bi bi-person-badge"></i> Manage roles
                    </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
