@extends('admin.layouts.app')
@section('page_title', 'Products')

@section('content')
<div class="admin-toolbar">
    <h6 class="admin-toolbar__title">Products <span class="admin-toolbar__count">{{ number_format($products->total()) }}</span></h6>
    @include('admin.partials.search', ['route' => route('admin.products.index'), 'placeholder' => 'Product name'])
    <a href="{{ route('admin.products.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Product</a>
</div>

@can('products.delete')
<form id="bulk-products" method="POST" action="{{ route('admin.products.bulkDestroy') }}"
      data-bulk data-bulk-noun="products">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        @can('products.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-products"></th>@endcan
                        <th>Image</th>
                        @include('admin.partials.sort', ['key' => 'name', 'label' => 'Name', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'category', 'label' => 'Category', 'first' => 'asc'])
                        <th>Variants</th>
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                <tr>
                        @can('products.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-products" name="ids[]" value="{{ $product->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">
                        <img src="{{ $product->image_url }}" alt="" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                    </td>
                    <td>
                        <strong>{{ $product->name }}</strong>
                        @if($product->is_featured) <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Featured</span> @endif
                        @if($product->is_bestseller) <span class="badge bg-success" style="font-size:0.65rem;">Best Seller</span> @endif
                    </td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td>
                        @foreach($product->variants as $v)
                            <span class="badge bg-light text-dark" style="font-size:0.75rem;">{{ $v->name }}: ৳{{ number_format($v->display_price) }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if($product->is_active) <span class="badge bg-success">Active</span>
                        @else <span class="badge bg-secondary">Inactive</span> @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('admin.combos.edit', $product) }}"
                               class="btn btn-sm {{ $product->is_combo ? 'btn-warning' : 'btn-outline-secondary' }}"
                               title="{{ $product->is_combo ? 'Combo contents' : 'Make this a combo' }}">
                                <i class="bi bi-box2"></i>
                            </a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="Delete this product?">
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
<div class="admin-pager">{{ $products->links() }}</div>

@endsection
