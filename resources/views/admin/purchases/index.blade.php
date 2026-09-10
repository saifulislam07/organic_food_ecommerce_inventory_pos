@extends('admin.layouts.app')

@section('title', 'Purchases')
@section('page_title', 'Purchase Management')

@section('content')
@can('purchases.delete')
<form id="bulk-purchases" method="POST" action="{{ route('admin.purchases.bulkDestroy') }}"
      data-bulk data-bulk-noun="purchases">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0 text-dark fw-bold">Recent Purchases</h5>
        @include('admin.partials.search', ['route' => route('admin.purchases.index'), 'placeholder' => 'Supplier, product or note'])
        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-cart-plus"></i> New Purchase
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @can('purchases.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-purchases"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'purchase_date', 'label' => 'Date'])
                        @include('admin.partials.sort', ['key' => 'supplier', 'label' => 'Supplier', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'product', 'label' => 'Product & Variant', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'unit_price', 'label' => 'Price/unit'])
                        @include('admin.partials.sort', ['key' => 'quantity', 'label' => 'Quantity'])
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        @can('purchases.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-purchases" name="ids[]" value="{{ $purchase->id }}"></td>@endcan
                        <td class="ps-4">{{ $purchase->purchase_date->format('d M, Y') }}</td>
                        <td><span class="fw-bold text-dark">{{ $purchase->supplier->name }}</span></td>
                        <td>
                            <strong class="text-dark">{{ $purchase->productVariant->product->name }}</strong><br>
                            <small class="text-muted">{{ $purchase->productVariant->name }}</small>
                        </td>
                        <td>৳{{ number_format($purchase->purchase_price) }}</td>
                        <td><span class="badge bg-success-subtle text-success fs-6">{{ $purchase->quantity }} units</span></td>
                        <td class="text-end pe-4">
                            <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST" class="d-inline" data-confirm="Revert this purchase? Stock will be decreased.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Revert
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="admin-table__empty">No purchase records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchases->hasPages())
    <div class="card-footer bg-white admin-pager">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
@endsection
