@extends('admin.layouts.app')

@section('title', 'Stock Adjustments')
@section('page_title', 'Stock Analysis & Adjustments')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-bold">Adjustment History</h5>
        <a href="{{ route('admin.adjustments.create') }}" class="btn btn-warning btn-sm">
            <i class="bi bi-tools"></i> New Adjustment
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Product & Variant</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    <tr>
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
                        <td colspan="6" class="text-center py-5 text-muted">No adjustment records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($adjustments->hasPages())
    <div class="card-footer bg-white">
        {{ $adjustments->links() }}
    </div>
    @endif
</div>
@endsection
