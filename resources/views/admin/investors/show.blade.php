@extends('admin.layouts.app')

@section('title', $investor->name)
@section('page_title', $investor->name)

@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <a href="{{ route('admin.investors.index') }}" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i> All investors
    </a>
    @if($investor->phone)
        <a href="tel:{{ preg_replace('/\s+/', '', $investor->phone) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-telephone"></i> {{ $investor->phone }}
        </a>
    @endif
    @unless($investor->is_active)
        <span class="badge bg-secondary">Inactive</span>
    @endunless

    <div class="ms-auto d-flex gap-2">
        @can('investments.create')
        <a href="{{ route('admin.investments.create') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-plus-lg"></i> Investment
        </a>
        @endcan
        @can('withdrawals.create')
        <a href="{{ route('admin.withdrawals.create') }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-dash-lg"></i> Withdrawal
        </a>
        @endcan
        @can('investors.edit')
        <a href="{{ route('admin.investors.edit', $investor) }}" class="btn btn-sm btn-outline-info">
            <i class="bi bi-pencil"></i> Edit
        </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Invested</h6>
                <h3 class="mb-0 text-success">৳{{ number_format($investor->totalInvested()) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Withdrawn</h6>
                <h3 class="mb-0 text-danger">৳{{ number_format($investor->totalWithdrawn()) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Balance</h6>
                <h3 class="mb-0 {{ $investor->balance() < 0 ? 'text-warning' : '' }}"
                    @if($investor->balance() >= 0) style="color: var(--primary);" @endif>
                    ৳{{ number_format($investor->balance()) }}
                </h3>
                @if($investor->balance() < 0)
                    {{-- Not a fault: profit taken out is money that was never
                         theirs to begin with, so it reads as a negative here. --}}
                    <small class="text-muted">Taken out more than put in — profit drawn.</small>
                @endif
            </div>
        </div>
    </div>
</div>

@if($investor->notes)
    <div class="alert alert-light border">{!! nl2br(e($investor->notes)) !!}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Statement</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Note</th>
                        <th class="text-end pe-4">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement as $row)
                    <tr>
                        <td class="ps-4">{{ $row['date']->format('d M, Y') }}</td>
                        <td>
                            @if($row['type'] === 'investment')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">In</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Out</span>
                            @endif
                        </td>
                        <td>
                            <i class="bi {{ \App\Support\PaymentAccounts::icon($row['account']) }}"></i>
                            {{ \App\Support\PaymentAccounts::label($row['account']) }}
                        </td>
                        <td class="text-muted small">{{ $row['notes'] ?: '—' }}</td>
                        <td class="text-end pe-4 fw-bold {{ $row['type'] === 'investment' ? 'text-success' : 'text-danger' }}">
                            {{ $row['type'] === 'investment' ? '+' : '−' }}৳{{ number_format($row['amount'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            Nothing has moved for this investor yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
