@extends('layouts.frontend')

@section('title', $product->meta_title ?? $product->name.' – MohiPure')
@section('meta_description', $product->meta_description ?? $product->short_description ?? $product->name)

@section('content')
@php
    $bn = app()->getLocale() == 'bn';
    $stock = $product->variants->sum(fn ($v) => $v->available_stock);
@endphp

<section class="product-detail">
    <div class="container">
        {{-- A slim trail rather than a banner: the title below is the page's
             one heading, and repeating it in a coloured band only crowded it. --}}
        <nav class="product-breadcrumb" aria-label="{{ $bn ? 'পথ' : 'Breadcrumb' }}">
            <a href="{{ route('home') }}">{{ $bn ? 'হোম' : 'Home' }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('shop') }}">{{ $bn ? 'শপ' : 'Shop' }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $product->name }}</span>
        </nav>

        <div class="row g-4 g-lg-5">
            <div class="col-lg-6">
                @php
                    // Thumbnail first, then the rest of the gallery.
                    $galleryUrls = $product->images->pluck('url');
                    $galleryUrls = $galleryUrls->isEmpty()
                        ? collect([$product->image_url])
                        : $galleryUrls->sortByDesc(fn ($url) => $url === $product->image_url)->values();

                    // No photo at all means the shared placeholder, which needs
                    // fitting rather than cropping.
                    $hasPhoto = $product->images->isNotEmpty() || filled($product->getRawOriginal('image'));
                @endphp
                {{-- Stays in view while the details column scrolls past it. --}}
                <div class="product-gallery-col {{ $hasPhoto ? '' : 'is-placeholder' }}">
                    <div
                        data-vue="ProductGalleryViewer"
                        data-props="{{ json_encode([
                            'images' => $galleryUrls,
                            'alt' => $product->name,
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="product-detail-info">
                    <a class="product-eyebrow" href="{{ route('shop', ['category' => $product->category->slug]) }}">
                        {{ $product->category->name }}
                    </a>

                    <h1 class="product-title">{{ $product->name }}</h1>

                    @if($product->short_description)
                        <p class="product-blurb">{{ $product->short_description }}</p>
                    @endif

                    @php
                        $whatsappTemplate = $bn
                            ? "হ্যালো! আমি এই প্রোডাক্টটি অর্ডার করতে চাই:

পণ্য: {product}
অপশন: {variant}
পরিমান: {quantity}

অনুগ্রহ করে ডেলিভরি সম্পর্কে তথ্য দিন।"
                            : "Hello! I would like to order this product:

Product: {product}
Variant: {variant}
Quantity: {quantity}

Please provide delivery info.";
                    @endphp
                    <div
                        data-vue="ProductPurchase"
                        data-props="{{ json_encode([
                            'productId' => $product->id,
                            'productName' => $product->name,
                            'variants' => $product->variants->map(fn ($v) => [
                                'id' => $v->id,
                                'name' => $v->name,
                                'price' => (float) $v->price,
                                'sale_price' => $v->sale_price === null ? null : (float) $v->sale_price,
                                'display_price' => (float) $v->display_price,
                                'stock' => $v->available_stock,
                            ])->values(),
                            'whatsappNumber' => \App\Models\Setting::get('whatsapp', '8801716952365'),
                            'whatsappTemplate' => $whatsappTemplate,
                            'labels' => [
                                'selectLabel' => $bn ? 'অপশন সিলেক্ট করুন' : 'Select option',
                                'quantityLabel' => $bn ? 'পরিমান' : 'Quantity',
                                'addToCart' => $bn ? 'কার্টে যোগ করুন' : 'Add to Cart',
                                'outOfStock' => $bn ? 'স্টক শেষ' : 'Out of Stock',
                                'whatsapp' => $bn ? 'WhatsApp এ অর্ডার' : 'Order via WhatsApp',
                                'selectOption' => $bn ? 'একটি অপশন সিলেক্ট করুন' : 'Please select an option',
                                'discount' => $bn ? 'ছাড়' : 'off',
                            ],
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>

                    <ul class="product-facts">
                        <li class="{{ $stock > 0 ? 'is-good' : 'is-bad' }}">
                            <i class="bi {{ $stock > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                            {{ $stock > 0
                                ? ($bn ? 'স্টকে আছে' : 'In stock')
                                : ($bn ? 'স্টক শেষ' : 'Out of stock') }}
                        </li>
                        <li>
                            <i class="bi bi-tag"></i>
                            <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
                        </li>
                        @if($product->is_preorder)
                            <li class="is-note">
                                <i class="bi bi-clock-history"></i>
                                {{ $bn ? 'প্রি-অর্ডার পাওয়া যাবে' : 'Pre-order available' }}
                            </li>
                        @endif
                    </ul>

                    @if($product->is_combo)
                        @include('products._combo-contents', ['product' => $product])
                    @endif
                </div>
            </div>
        </div>

        @if($product->description)
            <div class="product-panel">
                <h2 class="product-panel-title">{{ $bn ? 'পণ্যের বিবরণ' : 'Product Description' }}</h2>
                <div class="product-prose">{!! \App\Support\RichText::display($product->description) !!}</div>
            </div>
        @endif

        @if($related->count())
            <div class="product-related">
                <div class="section-header">
                    <div class="section-badge"><i class="bi bi-grid"></i> {{ $bn ? 'সংশ্লিষ্ট' : 'Related' }}</div>
                    <h2 class="section-title">{{ $bn ? 'সংশ্লিষ্ট পণ্যসমূহ' : 'Related Products' }}</h2>
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
