@extends('layouts.frontend')

@section('title', $product->meta_title ?? $product->name . ' – Mango Hut')
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('content')
<div class="page-header">
    <div class="container">
        <h1>{{ $product->name }}</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
            <li><span>/</span></li>
            <li><a href="{{ route('shop') }}">{{ app()->getLocale() == 'bn' ? 'শপ' : 'Shop' }}</a></li>
            <li><span>/</span></li>
            <li>{{ $product->name }}</li>
        </ul>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <!-- Product Image -->
            <div class="col-lg-6">
                <div class="product-gallery-main">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" id="mainImage">
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-detail-info">
                    <span class="section-badge mb-2">{{ $product->category->name }}</span>
                    <h1>{{ $product->name }}</h1>

                    @if($product->short_description)
                        <p class="text-muted mb-3">{{ $product->short_description }}</p>
                    @endif

                    <!-- Price Display -->
                    <div class="product-detail-price" id="priceDisplay">
                        @php $first = $product->variants->first(); @endphp
                        @if($first && $first->is_on_sale)
                            <span class="price-original" style="font-size:1.2rem;">৳{{ number_format($first->price) }}</span>
                            <span>৳{{ number_format($first->sale_price) }}</span>
                        @elseif($first)
                            ৳{{ number_format($first->price) }}
                        @endif
                    </div>

                    <!-- Variant Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ app()->getLocale() == 'bn' ? 'অপশন সিলেক্ট করুন:' : 'Select Option:' }}</label>
                        <div class="variant-options">
                            @foreach($product->variants as $i => $variant)
                            <button type="button"
                                    class="variant-btn {{ $i === 0 ? 'active' : '' }}"
                                    data-variant-id="{{ $variant->id }}"
                                    data-price="{{ $variant->display_price }}"
                                    data-original="{{ $variant->price }}"
                                    data-sale="{{ $variant->sale_price }}"
                                    data-stock="{{ $variant->stock }}"
                                    data-name="{{ $variant->name }}">
                                {{ $variant->name }}
                                <span class="variant-price">৳{{ number_format($variant->display_price) }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Quantity -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ app()->getLocale() == 'bn' ? 'পরিমান:' : 'Quantity:' }}</label>
                        <div class="qty-control">
                            <button class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="number" id="productQty" class="qty-value" value="1" min="1" max="20">
                            <button class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <button onclick="addProductToCart()" class="btn-primary-custom" id="addToCartBtn">
                            <i class="bi bi-cart-plus"></i> {{ app()->getLocale() == 'bn' ? 'কার্টে যোগ করুন' : 'Add to Cart' }}
                        </button>
                        <a href="#" id="whatsappOrderBtn" class="btn-whatsapp" target="_blank">
                            <i class="bi bi-whatsapp"></i> {{ app()->getLocale() == 'bn' ? 'WhatsApp এ অর্ডার করুন' : 'Order via WhatsApp' }}
                        </a>
                    </div>

                    <!-- Product Meta -->
                    <div class="border-top pt-3 mt-3">
                        <p class="mb-1"><strong>{{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি:' : 'Category:' }}</strong> <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></p>
                        @if($product->is_preorder)
                            <p class="mb-1"><span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> {{ app()->getLocale() == 'bn' ? 'প্রি-অর্ডার পাওয়া যাবে' : 'Pre-order Available' }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if($product->description)
        <div class="row mt-5">
            <div class="col-lg-8">
                <div class="card admin-card p-4">
                    <h4 class="mb-3" style="color: var(--primary-dark);">
                        <i class="bi bi-info-circle"></i> {{ app()->getLocale() == 'bn' ? 'পণ্যের বিবরণ' : 'Product Description' }}
                    </h4>
                    <div class="text-muted" style="line-height: 1.8;">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Related Products -->
        @if($related->count())
        <div class="mt-5">
            <div class="section-header">
                <div class="section-badge"><i class="bi bi-grid"></i> {{ app()->getLocale() == 'bn' ? 'সংশ্লিষ্ট' : 'Related' }}</div>
                <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'সংশ্লিষ্ট পণ্যসমূহ' : 'Related Products' }}</h2>
            </div>
            <div class="row g-4">
                @foreach($related as $rProduct)
                    <div class="col-xl-3 col-md-6 col-6">
                        @include('partials.product-card', ['product' => $rProduct])
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
let selectedVariantId = {{ $product->variants->first()->id ?? 'null' }};
let selectedVariantName = '{{ $product->variants->first()->name ?? '' }}';

// Variant selection
$('.variant-btn').on('click', function() {
    $('.variant-btn').removeClass('active');
    $(this).addClass('active');

    selectedVariantId = $(this).data('variant-id');
    selectedVariantName = $(this).data('name');
    const price = $(this).data('price');
    const original = $(this).data('original');
    const sale = $(this).data('sale');
    const stock = $(this).data('stock');

    let priceHtml = '';
    if (sale && sale < original) {
        priceHtml = '<span class="price-original" style="font-size:1.2rem;">৳' + Number(original).toLocaleString() + '</span> ৳' + Number(sale).toLocaleString();
    } else {
        priceHtml = '৳' + Number(original).toLocaleString();
    }
    $('#priceDisplay').html(priceHtml);

    if (stock <= 0) {
        $('#addToCartBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> {{ app()->getLocale() == "bn" ? "স্টক শেষ" : "Out of Stock" }}');
    } else {
        $('#addToCartBtn').prop('disabled', false).html('<i class="bi bi-cart-plus"></i> {{ app()->getLocale() == "bn" ? "কার্টে যোগ করুন" : "Add to Cart" }}');
    }

    updateWhatsAppLink();
});

function changeQty(delta) {
    const input = document.getElementById('productQty');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    input.value = val;
    updateWhatsAppLink();
}

function addProductToCart() {
    if (!selectedVariantId) {
        showToast('Please select an option', 'warning');
        return;
    }
    const qty = parseInt($('#productQty').val());
    addToCart({{ $product->id }}, selectedVariantId, qty);
}

function updateWhatsAppLink() {
    const qty = $('#productQty').val();
    const bnText = `হ্যালো! আমি এই প্রোডাক্টটি অর্ডার করতে চাই:\n\nপণ্য: {{ $product->name }}\nঅপশন: ${selectedVariantName}\nপরিমান: ${qty}\n\nঅনুগ্রহ করে ডেলিভরি সম্পর্কে তথ্য দিন।`;
    const enText = `Hello! I would like to order this product:\n\nProduct: {{ $product->name }}\nVariant: ${selectedVariantName}\nQuantity: ${qty}\n\nPlease provide delivery info.`;
    const text = '{{ app()->getLocale() == "bn" }}' === '1' ? bnText : enText;
    const encoded = encodeURIComponent(text);
    $('#whatsappOrderBtn').attr('href', `https://wa.me/{{ \App\Models\Setting::get('whatsapp', '8801716952365') }}?text=${encoded}`);
}

updateWhatsAppLink();

// Scroll animation
document.querySelectorAll('.fade-up').forEach(el => {
    new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
    }, {threshold: 0.1}).observe(el);
});
</script>
@endpush
