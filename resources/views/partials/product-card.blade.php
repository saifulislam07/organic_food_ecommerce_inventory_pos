{{-- Product Card Partial --}}
<div class="product-card fade-up">
    <div class="product-card-image">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        <div class="product-badge">
            @if($product->is_on_sale)
                <span class="badge-sale"><i class="bi bi-tag-fill"></i> {{ app()->getLocale() == 'bn' ? 'ছাড়' : 'Sale' }}</span>
            @endif
            @if($product->is_preorder)
                <span class="badge-preorder"><i class="bi bi-clock"></i> {{ app()->getLocale() == 'bn' ? 'প্রি-অর্ডার' : 'Pre-order' }}</span>
            @endif
            @if(!$product->is_in_stock && !$product->is_preorder)
                <span class="badge-outofstock">{{ app()->getLocale() == 'bn' ? 'স্টক শেষ' : 'Out of Stock' }}</span>
            @endif
        </div>
        <div class="product-quick-actions">
            <a href="{{ route('product.show', $product->slug) }}" class="quick-action-btn" title="View Details">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    <div class="product-card-body">
        <div class="product-category">{{ $product->category->name ?? '' }}</div>
        <h3 class="product-name">
            <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        <div class="product-price">
            @php
                $firstVariant = $product->variants->first();
            @endphp
            @if($firstVariant)
                @if($firstVariant->is_on_sale)
                    <span class="price-current">৳{{ number_format($firstVariant->sale_price) }}</span>
                    <span class="price-original">৳{{ number_format($firstVariant->price) }}</span>
                @else
                    <span class="price-current">{{ $product->price_range }}</span>
                @endif
            @endif
        </div>
    </div>
    <div class="product-card-footer">
        @if($product->variants->count() > 1)
            <a href="{{ route('product.show', $product->slug) }}" class="btn-add-cart">
                <i class="bi bi-eye"></i> {{ app()->getLocale() == 'bn' ? 'বিস্তারিত দেখুন' : 'View Options' }}
            </a>
        @elseif($firstVariant && $product->is_in_stock)
            <button onclick="addToCart({{ $product->id }}, {{ $firstVariant->id }})" class="btn-add-cart">
                <i class="bi bi-cart-plus"></i> {{ app()->getLocale() == 'bn' ? 'এখনই কিনুন' : 'Buy Now' }}
            </button>
        @else
            <button class="btn-add-cart" disabled>
                <i class="bi bi-x-circle"></i> {{ app()->getLocale() == 'bn' ? 'স্টক শেষ' : 'Out of Stock' }}
            </button>
        @endif
    </div>
</div>
