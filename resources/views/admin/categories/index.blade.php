@extends('admin.layouts.app')
@section('page_title', 'Categories')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.categories.index'), 'placeholder' => 'Category name'])
</div>
<div class="d-flex justify-content-end mb-4">
    <a href="{{ route('admin.categories.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Category</a>
</div>

@can('categories.delete')
<form id="bulk-categories" method="POST" action="{{ route('admin.categories.bulkDestroy') }}"
      data-bulk data-bulk-noun="categories">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background: var(--gray-100);">
                    <tr>
                        @can('categories.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-categories"></th>@endcan
                        <th style="padding: 14px 16px;">Image</th>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                <tr>
                        @can('categories.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-categories" name="ids[]" value="{{ $category->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">
                        <img src="{{ $category->image_url }}" alt="" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                    </td>
                    <td>
                        <strong>{{ $category->name }}</strong>
                    </td>
                    <td>{{ $category->products_count }}</td>
                    <td>
                        @if($category->is_active) <span class="badge bg-success">Active</span>
                        @else <span class="badge bg-secondary">Inactive</span> @endif
                    </td>
                    <td>{{ $category->sort_order }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" data-confirm="Delete this category?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $categories->links() }}</div>

@endsection
