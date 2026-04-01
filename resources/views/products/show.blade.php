@extends('layouts.frontend')

@section('title', $product->meta_title ?? $product->name . ' – Mango Hut')
@section('meta_description', $product->meta_description ?? $product->short_description)

@push('styles')
<style>
    .page-header {
        background-color: var(--primary-dark);
        padding: 60px 0;
        color: white;
    }
    .page-header h1 {
        color: white !important;
        margin-bottom: 10px;
    }
    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 0.9rem;
    }
    .breadcrumb-custom a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: var(--transition);
    }
    .breadcrumb-custom a:hover {
        color: white;
    }
    .breadcrumb-custom span {
        color: rgba(255, 255, 255, 0.5);
    }
    .breadcrumb-custom li:last-child {
        color: white;
        font-weight: 600;
    }

    /* Product Meta Modern Styles */
    .product-meta-section {
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .meta-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .meta-label {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
    }
    .meta-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #334155;
        text-decoration: none;
    }
    .link-primary-custom {
        color: var(--primary);
        transition: var(--transition);
        position: relative;
    }
    .link-primary-custom::after {
        content: "";
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary-light);
        transition: var(--transition);
        border-radius: 2px;
    }
    .link-primary-custom:hover::after {
        width: 100%;
    }

    /* Modern Variant Selection */
    .variant-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 10px;
    }

    .variant-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 120px;
        padding: 12px 16px;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .variant-btn:hover {
        border-color: var(--primary-light);
        background: var(--primary-50);
        transform: translateY(-2px);
    }

    .variant-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
    }

    .variant-btn .variant-name {
        font-size: 0.95rem;
        font-weight: 800;
        margin-bottom: 2px;
    }

    .variant-btn .variant-price {
        font-size: 0.85rem;
        font-weight: 600;
        opacity: 0.9;
    }

    .variant-btn.active .variant-price {
        color: white;
        opacity: 1;
    }

    /* Selection Badge */
    .variant-btn::before {
        content: "\F272"; /* bootstrap-icons check-circle-fill */
        font-family: "bootstrap-icons";
        position: absolute;
        top: -20px;
        right: 8px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        opacity: 0;
    }

    .variant-btn.active::before {
        top: 6px;
        opacity: 1;
    }
</style>
@endpush

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
                                <span class="variant-name">{{ $variant->name }}</span>
                                <span class="variant-price">৳{{ number_format($variant->display_price) }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

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
                    <div class="product-meta-section mt-5 pt-4 border-top">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="meta-item">
                                    <span class="meta-label">{{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি:' : 'Category:' }}</span>
                                    <a href="{{ route('shop', ['category' => $product->category->slug]) }}" class="meta-value link-primary-custom">
                                        {{ $product->category->name }}
                                    </a>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="meta-item">
                                    <span class="meta-label">{{ app()->getLocale() == 'bn' ? 'স্ট্যাটাস:' : 'Status:' }}</span>
                                    @php $stock = $product->variants->sum('stock'); @endphp
                                    <span class="meta-value {{ $stock > 0 ? 'text-green-600' : 'text-rose-500' }}">
                                        {{ $stock > 0 ? (app()->getLocale() == 'bn' ? 'স্টকে আছে' : 'In Stock') : (app()->getLocale() == 'bn' ? 'স্টক শেষ' : 'Out of Stock') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($product->is_preorder)
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold uppercase tracking-wider">
                                <i class="bi bi-clock-history"></i>
                                {{ app()->getLocale() == 'bn' ? 'প্রি-অর্ডার পাওয়া যাবে' : 'Pre-order Available' }}
                            </span>
                        </div>
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
