@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page_title', 'Customer List')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0 text-dark fw-bold">
            Customers <span class="admin-toolbar__count">{{ number_format($customers->total()) }}</span>
        </h5>
        @include('admin.partials.search', ['route' => route('admin.customers.index'), 'placeholder' => 'Name, email or mobile'])
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @include('admin.partials.sort', ['key' => 'name', 'label' => 'Customer', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'mobile', 'label' => 'Mobile', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'email', 'label' => 'Email', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'orders', 'label' => 'Orders', 'class' => 'text-center'])
                        @include('admin.partials.sort', ['key' => 'lifetime', 'label' => 'Lifetime Value', 'class' => 'text-end'])
                        @include('admin.partials.sort', ['key' => 'created_at', 'label' => 'Joined'])
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
                        <td colspan="7" class="admin-table__empty">
                            {{ request('search') ? 'No customer matches that search.' : 'No customers yet.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($customers->hasPages())
    <div class="card-footer bg-white admin-pager">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection
