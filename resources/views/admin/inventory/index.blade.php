@extends('admin.layouts.app')

@section('title', 'Inventory')
@section('page_title', 'Inventory Management')

@section('content')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger border-0 shadow-sm text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2 small text-uppercase fw-bold">Low Stock Alerts</h6>
                    <h3 class="mb-0">{{ $lowStockCount }} Items</h3>
                </div>
            </div>
        </div>
    </div>

    <div
        data-vue="InventoryTable"
        data-props="{{ json_encode([
            'rows' => $rows,
            'lowStockThreshold' => $lowStockThreshold,
        ], JSON_UNESCAPED_UNICODE) }}"
    ></div>

    @if($variants->hasPages())
        <div class="mt-3">
            {{ $variants->links() }}
        </div>
    @endif
@endsection
