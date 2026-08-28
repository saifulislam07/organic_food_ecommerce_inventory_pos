@extends('admin.layouts.app')

@section('title', $role ? 'Edit Role' : 'New Role')
@section('page_title', $role ? 'Edit '.$role->name : 'New Role')

@section('content')
<form action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST">
    @csrf
    @if($role) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="form-label fw-bold">Role name *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $role->name ?? '') }}"
                           placeholder="Cashier" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">
                        What this group of people does — Cashier, Stock Manager, Content Editor.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">How this works</h6>
                    <p class="text-muted small mb-2">
                        Without <strong>view</strong> the menu does not appear in the sidebar at all,
                        and the pages under it return 403.
                    </p>
                    <p class="text-muted small mb-0">
                        A dash means the action does not apply — you cannot delete the POS screen,
                        and a settings page has nothing to create.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">Menu Access</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-role-select="all">Select all</button>
                        <button type="button" class="btn btn-outline-secondary" data-role-select="none">Clear</button>
                    </div>
                </div>

                <div class="card-body p-0">
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
                                                       @checked(in_array($name, old('permissions', $assigned), true))>
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
            <i class="bi bi-save"></i> {{ $role ? 'Update Role' : 'Create Role' }}
        </button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-light px-4">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-role-select]').forEach((button) => {
        button.addEventListener('click', () => {
            const checked = button.dataset.roleSelect === 'all';

            document.querySelectorAll('input[name="permissions[]"]')
                .forEach((box) => { box.checked = checked; });
        });
    });
</script>
@endpush
