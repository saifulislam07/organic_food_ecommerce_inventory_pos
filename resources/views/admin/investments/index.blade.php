@extends('admin.layouts.app')

@section('title', 'Investments')
@section('page_title', 'Investments — Money In')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Invested</h6>
                <h3 class="mb-0 text-success">৳{{ number_format($totalAmount) }}</h3>
            </div>
        </div>
    </div>
</div>

@can('investments.delete')
<form id="bulk-investments" method="POST" action="{{ route('admin.investments.bulkDestroy') }}"
      data-bulk data-bulk-noun="investments">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0">Investment List</h5>
        @include('admin.partials.search', ['route' => route('admin.investments.index'), 'placeholder' => 'Investor or note'])
        @can('investments.create')
        <a href="{{ route('admin.investments.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Record Investment
        </a>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @can('investments.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-investments"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'invested_at', 'label' => 'Date'])
                        @include('admin.partials.sort', ['key' => 'investor', 'label' => 'Investor', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'account', 'label' => 'Received in', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'amount', 'label' => 'Amount', 'class' => 'text-end'])
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investments as $investment)
                    <tr>
                        @can('investments.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-investments" name="ids[]" value="{{ $investment->id }}"></td>@endcan
                        <td class="ps-4">{{ $investment->invested_at->format('d M, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.investors.show', $investment->investor_id) }}"
                               class="fw-bold text-decoration-none">
                                {{ $investment->investor?->name ?? '—' }}
                            </a>
                            @if($investment->notes)
                                <small class="d-block text-muted">{{ Str::limit($investment->notes, 60) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ \App\Support\PaymentAccounts::colour($investment->received_in) }}-subtle
                                         text-{{ \App\Support\PaymentAccounts::colour($investment->received_in) }} border">
                                <i class="bi {{ \App\Support\PaymentAccounts::icon($investment->received_in) }}"></i>
                                {{ \App\Support\PaymentAccounts::label($investment->received_in) }}
                            </span>
                        </td>
                        <td class="text-end text-success fw-bold">৳{{ number_format($investment->amount, 2) }}</td>
                        <td class="text-end pe-4">
                            @can('investments.edit')
                            <a href="{{ route('admin.investments.edit', $investment) }}" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('investments.delete')
                            <form action="{{ route('admin.investments.destroy', $investment) }}" method="POST"
                                  class="d-inline" data-confirm="Delete this investment?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="admin-table__empty">No investments recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($investments->hasPages())
    <div class="card-footer bg-white admin-pager">{{ $investments->links() }}</div>
    @endif
</div>
@endsection
