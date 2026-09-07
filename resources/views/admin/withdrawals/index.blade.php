@extends('admin.layouts.app')

@section('title', 'Withdrawals')
@section('page_title', 'Withdrawals — Money In')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.withdrawals.index'), 'placeholder' => 'Investor name, phone or note'])
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Withdrawn</h6>
                <h3 class="mb-0 text-danger">৳{{ number_format($totalAmount) }}</h3>
            </div>
        </div>
    </div>
</div>

@can('withdrawals.delete')
<form id="bulk-withdrawals" method="POST" action="{{ route('admin.withdrawals.bulkDestroy') }}"
      data-bulk data-bulk-noun="withdrawals">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Withdrawal List</h5>
        @can('withdrawals.create')
        <a href="{{ route('admin.withdrawals.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Record Withdrawal
        </a>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        @can('withdrawals.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-withdrawals"></th>@endcan
                        <th class="ps-4">Date</th>
                        <th>Investor</th>
                        <th>Paid from</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $withdrawal)
                    <tr>
                        @can('withdrawals.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-withdrawals" name="ids[]" value="{{ $withdrawal->id }}"></td>@endcan
                        <td class="ps-4">{{ $withdrawal->withdrawn_at->format('d M, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.investors.show', $withdrawal->investor_id) }}"
                               class="fw-bold text-decoration-none">
                                {{ $withdrawal->investor?->name ?? '—' }}
                            </a>
                            @if($withdrawal->notes)
                                <small class="d-block text-muted">{{ Str::limit($withdrawal->notes, 60) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ \App\Support\PaymentAccounts::colour($withdrawal->paid_from) }}-subtle
                                         text-{{ \App\Support\PaymentAccounts::colour($withdrawal->paid_from) }} border">
                                <i class="bi {{ \App\Support\PaymentAccounts::icon($withdrawal->paid_from) }}"></i>
                                {{ \App\Support\PaymentAccounts::label($withdrawal->paid_from) }}
                            </span>
                        </td>
                        <td class="text-end text-danger fw-bold">৳{{ number_format($withdrawal->amount, 2) }}</td>
                        <td class="text-end pe-4">
                            @can('withdrawals.edit')
                            <a href="{{ route('admin.withdrawals.edit', $withdrawal) }}" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('withdrawals.delete')
                            <form action="{{ route('admin.withdrawals.destroy', $withdrawal) }}" method="POST"
                                  class="d-inline" data-confirm="Delete this withdrawal?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No withdrawals recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($withdrawals->hasPages())
    <div class="card-footer bg-white">{{ $withdrawals->links() }}</div>
    @endif
</div>
@endsection
