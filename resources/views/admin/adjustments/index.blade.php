@extends('admin.layouts.app')

@section('title', 'Stock Adjustments')
@section('page_title', 'Stock Analysis & Adjustments')

@section('content')
@can('adjustments.delete')
<form id="bulk-adjustments" method="POST" action="{{ route('admin.adjustments.bulkDestroy') }}"
      data-bulk data-bulk-noun="adjustments">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0 text-dark fw-bold">Adjustment History</h5>
        @include('admin.partials.search', ['route' => route('admin.adjustments.index'), 'placeholder' => 'Product, type or reason'])
        <a href="{{ route('admin.adjustments.create') }}" class="btn btn-warning btn-sm">
            <i class="bi bi-tools"></i> New Adjustment
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @can('adjustments.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-adjustments"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'adjustment_date', 'label' => 'Date'])
                        @include('admin.partials.sort', ['key' => 'product', 'label' => 'Product & Variant', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'type', 'label' => 'Type', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'quantity', 'label' => 'Quantity'])
                        @include('admin.partials.sort', ['key' => 'reason', 'label' => 'Reason', 'first' => 'asc'])
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    <tr>
                        @can('adjustments.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-adjustments" name="ids[]" value="{{ $adj->id }}"></td>@endcan
                        <td class="ps-4">{{ $adj->adjustment_date->format('d M, Y') }}</td>
                        <td>
                            <strong class="text-dark">{{ $adj->productVariant->product->name }}</strong><br>
                            <small class="text-muted">{{ $adj->productVariant->name }}</small>
                        </td>
                        <td>
                            @php
                                $badgeClass = match($adj->type) {
                                    'lost' => 'bg-danger text-white',
                                    'damage' => 'bg-warning text-dark',
                                    'returned' => 'bg-success text-white',
                                    default => 'bg-secondary text-white'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} text-uppercase">{{ $adj->type }}</span>
                        </td>
                        <td>
                            <span class="fw-bold {{ $adj->type == 'returned' ? 'text-success' : 'text-danger' }}">
                                {{ $adj->type == 'returned' ? '+' : '-' }}{{ $adj->quantity }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ Str::limit($adj->reason, 30) }}</small></td>
                        <td class="text-end pe-4">
                            <form action="{{ route('admin.adjustments.destroy', $adj) }}" method="POST" class="d-inline" onsubmit="return confirm('Revert this adjustment? Stock will be updated.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-arrow-counterclockwise"></i> Revert
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="admin-table__empty">No adjustment records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($adjustments->hasPages())
    <div class="card-footer bg-white admin-pager">
        {{ $adjustments->links() }}
    </div>
    @endif
</div>
@endsection
