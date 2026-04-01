@extends('layouts.frontend')

@section('title', 'Shop – Mango Hut')
@section('meta_description', 'Browse our collection of organic products - mangoes, ghee, honey, mustard oil and more.')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(rgba(27, 67, 50, 0.8), rgba(27, 67, 50, 0.8)), url('/images/header-bg.jpg');
        background-size: cover;
        background-position: center;
        padding: 60px 0;
        color: white;
        text-align: center;
        margin-bottom: 40px;
    }
    .breadcrumb-custom {
        display: inline-flex;
        list-style: none;
        padding: 0;
        margin-top: 15px;
        gap: 10px;
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .breadcrumb-custom a { color: white; text-decoration: none; }
    .breadcrumb-custom span { color: var(--secondary); }

    .shop-sidebar {
        background: white;
        padding: 25px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-100);
        margin-bottom: 25px;
    }
    .sidebar-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--gray-100);
        color: var(--dark);
    }
    .category-filter-list { list-style: none; padding: 0; margin: 0; }
    .category-filter-list li { margin-bottom: 12px; }
    .category-filter-list a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-decoration: none;
        color: #555;
        font-weight: 500;
        transition: var(--transition);
        padding: 8px 12px;
        border-radius: var(--radius-sm);
    }
    .category-filter-list a:hover, .category-filter-list a.active {
        background: var(--gray-100);
        color: var(--primary);
    }
    .category-count {
        background: var(--gray-100);
        color: var(--gray-500);
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    .active .category-count { background: var(--primary); color: white; }

    @media (max-width: 991px) {
        .page-header { padding: 40px 0; }
        .sidebar-collapse-toggle {
            width: 100%;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="container">
        <h1>{{ app()->getLocale() == 'bn' ? 'শপ' : 'Shop' }}</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
            <li><span>/</span></li>
            <li>{{ app()->getLocale() == 'bn' ? 'শপ' : 'Shop' }}</li>
        </ul>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <button class="btn btn-outline-primary d-lg-none w-100 mb-3 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse">
                    <span><i class="bi bi-filter"></i> {{ app()->getLocale() == 'bn' ? 'ফিল্টার করুন' : 'Filters' }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                
                <div class="collapse d-lg-block" id="sidebarCollapse">
                    <!-- Search -->
                    <div class="shop-sidebar">
                        <h5 class="sidebar-title">{{ app()->getLocale() == 'bn' ? 'অনুসন্ধান' : 'Search' }}</h5>
                        <form action="{{ route('shop') }}" method="GET">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="{{ app()->getLocale() == 'bn' ? 'পণ্য খুঁজুন...' : 'Search products...' }}"
                                       value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="shop-sidebar">
                        <h5 class="sidebar-title">{{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি' : 'Categories' }}</h5>
                        <ul class="category-filter-list">
                            <li>
                                <a href="{{ route('shop') }}" class="{{ !request('category') ? 'active' : '' }}">
                                    {{ app()->getLocale() == 'bn' ? 'সব পণ্য' : 'All Products' }}
                                    <span class="category-count">{{ $products->total() }}</span>
                                </a>
                            </li>
                            @foreach($categories as $category)
                            <li>
                                <a href="{{ route('shop', ['category' => $category->slug]) }}"
                                   class="{{ request('category') == $category->slug ? 'active' : '' }}">
                                    {{ $category->name }}
                                    <span class="category-count">{{ $category->products_count }}</span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-9">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                    <p class="mb-0 text-muted flex-shrink-0" id="showing-text">
                        @if(app()->getLocale() == 'bn')
                            {{ $products->total() }} টি পণ্যের মধ্যে {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} টি দেখানো হচ্ছে
                        @else
                            Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
                        @endif
                    </p>
                    <form action="{{ route('shop') }}" method="GET" class="d-flex align-items-center gap-2">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <select name="sort" class="sort-select form-select form-select-sm" onchange="this.form.submit()">
                            <option value="latest" {{ $sort == 'latest' ? 'selected' : '' }}>{{ app()->getLocale() == 'bn' ? 'সর্বশেষ' : 'Latest' }}</option>
                            <option value="name_asc" {{ $sort == 'name_asc' ? 'selected' : '' }}>{{ app()->getLocale() == 'bn' ? 'নাম (A-Z)' : 'Name (A-Z)' }}</option>
                            <option value="name_desc" {{ $sort == 'name_desc' ? 'selected' : '' }}>{{ app()->getLocale() == 'bn' ? 'নাম (Z-A)' : 'Name (Z-A)' }}</option>
                            <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>{{ app()->getLocale() == 'bn' ? 'মূল্য: কম → বেশি' : 'Price: Low → High' }}</option>
                            <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>{{ app()->getLocale() == 'bn' ? 'মূল্য: বেশি → কম' : 'Price: High → Low' }}</option>
                        </select>
                    </form>
                </div>

                @if($products->count())
                <div class="row g-4" id="product-grid">
                    @include('shop._products', ['products' => $products])
                </div>

                <div class="mt-5 d-flex justify-content-center" id="load-more-container">
                    @if($products->hasMorePages())
                        <button id="load-more-btn" class="btn-primary-custom px-5" data-page="2">
                            <i class="bi bi-arrow-clockwise"></i> {{ app()->getLocale() == 'bn' ? 'আরও দেখুন' : 'Load More' }}
                        </button>
                    @endif
                </div>
                @else
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                    <h3>{{ app()->getLocale() == 'bn' ? 'কোনো পণ্য পাওয়া যায়নি' : 'No products found' }}</h3>
                    <p class="text-muted">{{ app()->getLocale() == 'bn' ? 'দুঃখিত, আপনার অনুসন্ধানের সাথে মিলে এমন কোনো পণ্য নেই।' : 'Try adjusting your filters or search terms.' }}</p>
                    <a href="{{ route('shop') }}" class="btn-primary-custom mt-3">
                        <i class="bi bi-arrow-left"></i> {{ app()->getLocale() == 'bn' ? 'সব পণ্য দেখুন' : 'View All Products' }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
    }, {threshold: 0.1});

    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

    $('#load-more-btn').click(function() {
        const btn = $(this);
        const page = parseInt(btn.data('page')) || 2;
        const container = $('#product-grid');
        const showingText = $('#showing-text');
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> {{ app()->getLocale() == "bn" ? "লোড হচ্ছে..." : "Loading..." }}');

        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        const url = window.location.pathname + '?' + params.toString();

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) {
                if (data && data.html && data.html.trim().length > 0) {
                    container.append(data.html);
                    btn.data('page', page + 1);
                    if (data.showing) showingText.text(data.showing);
                    
                    if (!data.hasMore) {
                        $('#load-more-container').fadeOut();
                    } else {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                    
                    // Re-init observer for new items
                    document.querySelectorAll('.fade-up:not(.visible)').forEach(el => observer.observe(el));
                } else {
                    $('#load-more-container').fadeOut();
                }
            },
            error: function(xhr) {
                console.error('Load more failed', xhr);
                btn.prop('disabled', false).html('<i class="bi bi-exclamation-triangle"></i> {{ app()->getLocale() == "bn" ? "আবার চেষ্টা করুন" : "Try Again" }}');
            }
        });
    });
});
</script>
@endpush
