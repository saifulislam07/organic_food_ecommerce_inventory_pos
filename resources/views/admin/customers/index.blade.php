@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page_title', 'Customer List')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-bold">
            Customers <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $customers->total() }}</span>
        </h5>
        <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex gap-2" style="max-width:340px;">
            <input type="search" name="search" class="form-control form-control-sm"
                   placeholder="Name, email or mobile" value="{{ request('search') }}">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Customer</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Lifetime Value</th>
                        <th>Joined</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td>
                            @if($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}" class="text-decoration-none">{{ $customer->mobile }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $customer->email ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $customer->orders_count ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">
                                {{ $customer->orders_count }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">৳{{ number_format((float) $customer->orders_total) }}</td>
                        <td class="text-muted small">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            {{ request('search') ? 'No customer matches that search.' : 'No customers yet.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($customers->hasPages())
    <div class="card-footer bg-white">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection
