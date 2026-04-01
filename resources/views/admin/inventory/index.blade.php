@extends('admin.layouts.app')

@section('title', 'Inventory')
@section('page_title', 'Inventory Management')

@section('content')
<div class="row mb-4 text-white">
    <div class="col-md-3">
        <div class="card bg-danger border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-white-50 mb-2 small text-uppercase fw-bold">Low Stock Alerts</h6>
                <h3 class="mb-0">{{ \App\Models\ProductVariant::where('stock', '<', 5)->count() }} Items</h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Product Stock Levels</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Product & Variant</th>
                        <th>Current Stock</th>
                        <th>Price</th>
                        <th class="text-end pe-4">Quick Update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($variants as $variant)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $variant->product->image_url }}" alt="{{ $variant->product->name }}" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">{{ $variant->product->name }}</h6>
                                    <small class="text-muted">{{ $variant->weight }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $variant->stock < 5 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} fs-6 px-3 py-2">
                                {{ $variant->stock }} in stock
                            </span>
                        </td>
                        <td>৳{{ number_format($variant->price) }}</td>
                        <td class="text-end pe-4">
                            <form action="{{ route('admin.inventory.update', $variant) }}" method="POST" class="d-inline-flex gap-2 justify-content-end align-items-center stock-form">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="stock" class="form-control form-control-sm" value="{{ $variant->stock }}" style="width: 80px;" min="0">
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($variants->hasPages())
    <div class="card-footer bg-white">
        {{ $variants->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.stock-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const stock = form.find('input[name="stock"]').val();
        const btn = form.find('button');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: url,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                stock: stock
            },
            success: function(response) {
                btn.prop('disabled', false).html('Update');
                // Flash success color briefly
                const row = form.closest('tr');
                row.addClass('bg-success-subtle');
                setTimeout(() => row.removeClass('bg-success-subtle'), 1000);
            },
            error: function() {
                btn.prop('disabled', false).html('Update');
                alert('Something went wrong. Please try again.');
            }
        });
    });
});
</script>
@endpush
