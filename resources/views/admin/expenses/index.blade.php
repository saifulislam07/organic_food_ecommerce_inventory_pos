@extends('admin.layouts.app')

@section('title', 'Expenses')
@section('page_title', 'Expense Management')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Expenses</h6>
                <h3 class="mb-0 text-danger">৳{{ number_format($totalAmount) }}</h3>
            </div>
        </div>
    </div>
</div>

@can('expenses.delete')
<form id="bulk-expenses" method="POST" action="{{ route('admin.expenses.bulkDestroy') }}"
      data-bulk data-bulk-noun="expenses">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0">Expense List</h5>
        @include('admin.partials.search', ['route' => route('admin.expenses.index'), 'placeholder' => 'Title, category or note'])
        <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Expense
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @can('expenses.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-expenses"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'expense_date', 'label' => 'Date'])
                        @include('admin.partials.sort', ['key' => 'title', 'label' => 'Title', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'category', 'label' => 'Category', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'amount', 'label' => 'Amount'])
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        @can('expenses.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-expenses" name="ids[]" value="{{ $expense->id }}"></td>@endcan
                        <td class="ps-4">{{ $expense->expense_date->format('d M, Y') }}</td>
                        <td>
                            <strong>{{ $expense->title }}</strong>
                            @if($expense->notes)
                                <small class="d-block text-muted">{{ $expense->notes }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $expense->category }}</span></td>
                        <td class="text-danger fw-bold">৳{{ number_format($expense->amount, 2) }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="d-inline" data-confirm="Are you sure?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="admin-table__empty">No expenses recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer bg-white admin-pager">
        {{ $expenses->links() }}
    </div>
    @endif
</div>

@endsection
