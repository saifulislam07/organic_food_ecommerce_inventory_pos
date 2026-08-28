@extends('admin.layouts.app')

@section('title', 'Units')
@section('page_title', 'Measurement Units')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.units.index'), 'placeholder' => 'Name or short code'])
</div>
@can('units.delete')
<form id="bulk-units" method="POST" action="{{ route('admin.units.bulkDestroy') }}"
      data-bulk data-bulk-noun="units">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-bold">Units</h5>
        <a href="{{ route('admin.units.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Unit
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        @can('units.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-units"></th>@endcan
                        <th class="ps-4">Name</th>
                        <th>বাংলা</th>
                        <th>Short Code</th>
                        <th class="text-center">Used By</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr>
                        @can('units.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-units" name="ids[]" value="{{ $unit->id }}" @disabled($unit->variants_count)></td>@endcan
                        <td class="ps-4 fw-bold text-dark">{{ $unit->name }}</td>
                        <td>{{ $unit->name_bn ?? '—' }}</td>
                        <td><code>{{ $unit->short_code }}</code></td>
                        <td class="text-center">
                            <span class="badge {{ $unit->variants_count ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">
                                {{ $unit->variants_count }}
                            </span>
                        </td>
                        <td>
                            @if($unit->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.units.edit', $unit) }}" class="btn btn-outline-info">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.units.destroy', $unit) }}" method="POST" class="d-inline"
                                      data-confirm="Delete this unit?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" @disabled($unit->variants_count)>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            No units yet. Add one, or run <code>php artisan db:seed --class=UnitSeeder</code>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($units->hasPages())
    <div class="card-footer bg-white">{{ $units->links() }}</div>
    @endif
</div>

@endsection
