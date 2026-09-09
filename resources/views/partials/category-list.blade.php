{{--
    The vertical category list, in one place.

    It is rendered twice on the front page — inside the header's "All
    Categories" dropdown and inside the rail beside the hero — so the two can
    never drift apart. `withCount('products')` on the query switches the count
    on; without it the column is simply left off.

    @include('partials.category-list', ['categories' => $categories])
--}}
@foreach($categories as $category)
    <a href="{{ route('shop', ['category' => $category->slug]) }}" class="pc-cat-link">
        <img src="{{ $category->image_url }}" alt="" class="pc-cat-thumb" loading="lazy">
        <span class="pc-cat-name">{{ $category->name }}</span>
        @isset($category->products_count)
            <span class="pc-cat-count">{{ $category->products_count }}</span>
        @endisset
    </a>
@endforeach
<a href="{{ route('shop') }}" class="pc-cat-all">
    {{ app()->getLocale() == 'bn' ? 'সব পণ্য দেখুন' : 'View All Products' }} <i class="bi bi-arrow-right"></i>
</a>
