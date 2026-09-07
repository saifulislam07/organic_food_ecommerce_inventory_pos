@extends('admin.layouts.app')

@section('title', 'Investors')
@section('page_title', 'Investors')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.investors.index'), 'placeholder' => 'Name, phone or note'])
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Invested</h6>
                <h3 class="mb-0 text-success">৳{{ number_format($totals['invested']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Withdrawn</h6>
                <h3 class="mb-0 text-danger">৳{{ number_format($totals['withdrawn']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Capital in the Business</h6>
                <h3 class="mb-0" style="color: var(--primary);">
                    ৳{{ number_format($totals['invested'] - $totals['withdrawn']) }}
                </h3>
            </div>
        </div>
    </div>
</div>

@can('investors.delete')
<form id="bulk-investors" method="POST" action="{{ route('admin.investors.bulkDestroy') }}"
      data-bulk data-bulk-noun="investors">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Investor List</h5>
        @can('investors.create')
        <a href="{{ route('admin.investors.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Investor
        </a>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        @can('investors.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-investors"></th>@endcan
                        <th class="ps-4">Name</th>
                        <th>Phone</th>
                        <th class="text-end">Invested</th>
                        <th class="text-end">Withdrawn</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investors as $investor)
                    <tr>
                        @can('investors.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-investors" name="ids[]" value="{{ $investor->id }}"></td>@endcan
                        <td class="ps-4">
                            <a href="{{ route('admin.investors.show', $investor) }}" class="fw-bold text-decoration-none">
                                {{ $investor->name }}
                            </a>
                            @unless($investor->is_active)
                                <span class="badge bg-secondary ms-1">Inactive</span>
                            @endunless
                            @if($investor->notes)
                                <small class="d-block text-muted">{{ Str::limit($investor->notes, 60) }}</small>
                            @endif
                        </td>
                        <td>{{ $investor->phone ?: '—' }}</td>
                        <td class="text-end text-success">৳{{ number_format($investor->totalInvested()) }}</td>
                        <td class="text-end text-danger">৳{{ number_format($investor->totalWithdrawn()) }}</td>
                        {{-- Negative is not an error: they have drawn profit out. --}}
                        <td class="text-end fw-bold {{ $investor->balance() < 0 ? 'text-warning' : '' }}">
                            ৳{{ number_format($investor->balance()) }}
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-sm btn-outline-secondary me-1"
                               title="Statement">
                                <i class="bi bi-list-columns-reverse"></i>
                            </a>
                            @can('investors.edit')
                            <a href="{{ route('admin.investors.edit', $investor) }}" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('investors.delete')
                            <form action="{{ route('admin.investors.destroy', $investor) }}" method="POST" class="d-inline"
                                  data-confirm="Delete this investor?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            No investors yet. Add one before recording money in or out.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($investors->hasPages())
    <div class="card-footer bg-white">{{ $investors->links() }}</div>
    @endif
</div>
@endsection
