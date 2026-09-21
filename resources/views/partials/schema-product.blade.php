{{--
    The product itself, in the form Google reads — what puts the price and
    the in-stock line straight into the search result.

    Deliberately no aggregateRating: reviews are collected and approved, but
    nothing on the product page shows them yet. Google requires a rating to be
    visible on the same page as its markup, and marking up stars a visitor
    cannot see is what earns a manual action. Show the reviews first, then add
    it here.

    Two schemas go out together, so the result can also show the
    Home › Shop › Category › Product trail under the link.

    @include('partials.schema-product', ['product' => $product])
--}}
@php
    $low = $product->lowest_price;
    $high = $product->highest_price;

    $description = trim(strip_tags(
        $product->meta_description
            ?: ($product->short_description ?: $product->description ?: '')
    ));

    $images = $product->images->pluck('url');
    if ($images->isEmpty()) {
        $images = collect([$product->image_url]);
    }

    // One price or a range, depending on what the variants cost.
    $offer = array_filter([
        '@type' => $low == $high ? 'Offer' : 'AggregateOffer',
        'priceCurrency' => 'BDT',
        'price' => $low == $high ? (string) $low : null,
        'lowPrice' => $low == $high ? null : (string) $low,
        'highPrice' => $low == $high ? null : (string) $high,
        'offerCount' => $low == $high ? null : $product->variants->count(),
        'availability' => $product->is_in_stock
            ? 'https://schema.org/InStock'
            // is_preorderable, not the raw flag: it also checks the pre-order
            // terms are actually set up, so this cannot advertise an order the
            // page would refuse to take.
            : ($product->is_preorderable ? 'https://schema.org/PreOrder' : 'https://schema.org/OutOfStock'),
        'url' => route('product.show', $product->slug),
    ], fn ($value) => $value !== null);

    $schemaProduct = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $images->values()->all(),
        'description' => $description ?: null,
        'sku' => $product->variants->first()?->sku,
        'category' => $product->category?->name,
        'brand' => [
            '@type' => 'Brand',
            'name' => \App\Models\Setting::get('site_title', 'BaburhashiBD'),
        ],
        'offers' => $offer,
    ], fn ($value) => $value !== null && $value !== []);

    // Mirrors the visible trail above the product title.
    $trail = collect([
        ['name' => app()->getLocale() == 'bn' ? 'হোম' : 'Home', 'item' => route('home')],
        ['name' => app()->getLocale() == 'bn' ? 'শপ' : 'Shop', 'item' => route('shop')],
    ]);

    if ($product->category) {
        $trail->push([
            'name' => $product->category->name,
            'item' => route('shop', ['category' => $product->category->slug]),
        ]);
    }

    $trail->push(['name' => $product->name, 'item' => route('product.show', $product->slug)]);

    $breadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $trail->values()->map(fn ($crumb, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $crumb['name'],
            'item' => $crumb['item'],
        ])->all(),
    ];

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT;
@endphp
<script type="application/ld+json">
{!! json_encode($schemaProduct, $flags) !!}
</script>
<script type="application/ld+json">
{!! json_encode($breadcrumbs, $flags) !!}
</script>
