@extends('admin.layouts.app')

@section('title', 'Combos')
@section('page_title', 'Combo Products')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.combos.index'), 'placeholder' => 'Combo name'])
</div>
@can('combos.delete')
<form id="bulk-combos" method="POST" action="{{ route('admin.combos.bulkDestroy') }}"
      data-bulk data-bulk-noun="combos">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            Combos <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $combos->total() }}</span>
        </h5>
        @can('combos.create')
            <a href="{{ route('admin.combos.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Combo
            </a>
        @endcan
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        @can('combos.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-combos"></th>@endcan
                        <th class="ps-4">Combo</th>
                        <th>Contents</th>
                        <th class="text-center">Can Build</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($combos as $combo)
                    <tr>
                        @can('combos.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-combos" name="ids[]" value="{{ $combo->id }}"></td>@endcan
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $combo->image_url }}" alt="" class="rounded shadow-sm"
                                     style="width:44px;height:44px;object-fit:cover;">
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $combo->name }}</span>
                                    <small class="text-muted">{{ $combo->category->name ?? '—' }}</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            @foreach($combo->variants as $variant)
                                <div class="mb-1">
                                    <span class="text-muted small">{{ $variant->name }}:</span>
                                    @forelse($variant->comboItems as $item)
                                        <span class="badge bg-light text-dark border">
                                            {{ $item->quantity }} × {{ $item->component->product->name ?? '?' }}
                                            <span class="text-muted">({{ $item->component->name ?? '' }})</span>
                                        </span>
                                    @empty
                                        <span class="text-muted small fst-italic">nothing yet</span>
                                    @endforelse
                                </div>
                            @endforeach
                        </td>

                        <td class="text-center">
                            @php $count = $buildable[$combo->id] ?? 0; @endphp
                            <span class="badge fs-6 px-3 py-2 {{ $count > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ $count }}
                            </span>
                        </td>

                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                @can('combos.edit')
                                    <a href="{{ route('admin.combos.edit', $combo) }}" class="btn btn-outline-warning" title="Combo contents">
                                        <i class="bi bi-box2"></i>
                                    </a>
                                @endcan
                                @can('products.edit')
                                    <a href="{{ route('admin.products.edit', $combo) }}" class="btn btn-outline-info" title="Product details">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-box2 fs-1 d-block mb-2 opacity-25"></i>
                            No combos yet. A combo bundles existing products and draws its stock from them.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($combos->hasPages())
        <div class="card-footer bg-white">{{ $combos->links() }}</div>
    @endif
</div>

<div class="alert alert-light border mt-4 small mb-0">
    <i class="bi bi-info-circle"></i>
    A combo holds no stock of its own — what it can sell is limited by whichever
    component runs out first, and every sale draws those components down.
</div>
@endsection
