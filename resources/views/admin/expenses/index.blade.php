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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Expense List</h5>
        <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Expense
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
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
                            <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                        <td colspan="5" class="text-center py-5 text-muted">No expenses recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer bg-white">
        {{ $expenses->links() }}
    </div>
    @endif
</div>
@endsection
