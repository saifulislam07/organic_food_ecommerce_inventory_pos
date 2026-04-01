@extends('admin.layouts.app')
@section('page_title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <form action="{{ route('admin.products.index') }}" method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control" placeholder="Search products..." value="{{ request('search') }}">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Product</a>
</div>

<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background: var(--gray-100);">
                    <tr>
                        <th style="padding: 14px 16px;">Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Variants</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                <tr>
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
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?')">
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
<div class="mt-3">{{ $products->links() }}</div>
@endsection
