@extends('admin.layouts.app')

@section('title', 'Purchases')
@section('page_title', 'Purchase Management')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-bold">Recent Purchases</h5>
        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-cart-plus"></i> New Purchase
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Supplier</th>
                        <th>Product & Variant</th>
                        <th>Price/unit</th>
                        <th>Quantity</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        <td class="ps-4">{{ $purchase->purchase_date->format('d M, Y') }}</td>
                        <td><span class="fw-bold text-dark">{{ $purchase->supplier->name }}</span></td>
                        <td>
                            <strong class="text-dark">{{ $purchase->productVariant->product->name }}</strong><br>
                            <small class="text-muted">{{ $purchase->productVariant->name }}</small>
                        </td>
                        <td>৳{{ number_format($purchase->purchase_price) }}</td>
                        <td><span class="badge bg-success-subtle text-success fs-6">{{ $purchase->quantity }} units</span></td>
                        <td class="text-end pe-4">
                            <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Revert this purchase? Stock will be decreased.')">
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
                        <td colspan="6" class="text-center py-5 text-muted">No purchase records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchases->hasPages())
    <div class="card-footer bg-white">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
@endsection
