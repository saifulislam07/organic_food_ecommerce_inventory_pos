@extends('admin.layouts.app')

@section('title', 'Suppliers')
@section('page_title', 'Supplier Management')

@section('content')
@can('suppliers.delete')
<form id="bulk-suppliers" method="POST" action="{{ route('admin.suppliers.bulkDestroy') }}"
      data-bulk data-bulk-noun="suppliers">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 admin-toolbar admin-toolbar--flush">
        <h5 class="mb-0 text-dark fw-bold">Supplier List</h5>
        @include('admin.partials.search', ['route' => route('admin.suppliers.index'), 'placeholder' => 'Name, phone or email'])
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Supplier
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle admin-table">
                <thead>
                    <tr>
                        @can('suppliers.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-suppliers"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'name', 'label' => 'Name', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'contact_person', 'label' => 'Contact Person', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'phone', 'label' => 'Phone', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'email', 'label' => 'Email', 'first' => 'asc'])
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        @can('suppliers.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-suppliers" name="ids[]" value="{{ $supplier->id }}"></td>@endcan
                        <td class="ps-4">
                            <span class="fw-bold text-dark">{{ $supplier->name }}</span>
                        </td>
                        <td>{{ $supplier->contact_person ?? 'N/A' }}</td>
                        <td>{{ $supplier->phone ?? 'N/A' }}</td>
                        <td>{{ $supplier->email ?? 'N/A' }}</td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-outline-info">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST" class="d-inline" data-confirm="Delete this supplier?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="admin-table__empty">No suppliers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
    <div class="card-footer bg-white admin-pager">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>

@endsection
