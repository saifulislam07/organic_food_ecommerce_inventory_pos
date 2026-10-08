{{--
    The product itself, in the form Google reads — what puts the price and
    the in-stock line straight into the search result.

    aggregateRating and review go out only when the product has approved
    reviews, and only for the reviews the page's Customer Reviews panel shows.
    Google requires a rating to be visible on the same page as its markup, and
    marking up stars a visitor cannot see is what earns a manual action.

    Two schemas go out together, so the result can also show the
    Home › Shop › Category › Product trail under the link.

    @include('partials.schema-product', compact('product', 'reviews', 'reviewCount', 'reviewAverage'))
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

    // Delivery as the checkout charges it. Google takes one rate per country
    // and cannot tell inside Dhaka from outside, so this states the nationwide
    // fee and the longest wait — never less than a buyer will actually pay.
    $threshold = (float) \App\Models\Setting::get('free_delivery_threshold', 2000);
    $shippingFee = $low >= $threshold
        ? 0
        : (float) \App\Models\Setting::get('shipping_fee_outside', 120);

    $shippingDetails = [
        '@type' => 'OfferShippingDetails',
        'shippingRate' => [
            '@type' => 'MonetaryAmount',
            'value' => (string) $shippingFee,
            'currency' => 'BDT',
        ],
        'shippingDestination' => [
            '@type' => 'DefinedRegion',
            'addressCountry' => 'BD',
        ],
        // Shipping policy: 24–48 hours in the city, 3–5 days elsewhere.
        'deliveryTime' => [
            '@type' => 'ShippingDeliveryTime',
            'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY'],
            'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 5, 'unitCode' => 'DAY'],
        ],
    ];

    // Mirrors the return-policy page: faults only, 7 days from delivery, and
    // the delivery charge refunded, so the return costs the buyer nothing.
    $returnPolicy = [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => 'BD',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays' => 7,
        'returnMethod' => 'https://schema.org/ReturnByMail',
        'returnFees' => 'https://schema.org/FreeReturn',
        'merchantReturnLink' => url('/return-policy'),
    ];

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
        'shippingDetails' => $shippingDetails,
        'hasMerchantReturnPolicy' => $returnPolicy,
    ], fn ($value) => $value !== null);

    $reviews = $reviews ?? collect();
    $reviewCount = $reviewCount ?? 0;

    $aggregateRating = $reviewCount ? [
        '@type' => 'AggregateRating',
        'ratingValue' => (string) $reviewAverage,
        'reviewCount' => $reviewCount,
        'bestRating' => '5',
        'worstRating' => '1',
    ] : null;

    $reviewMarkup = $reviews->map(fn ($review) => array_filter([
        '@type' => 'Review',
        'author' => ['@type' => 'Person', 'name' => $review->customer_name],
        'datePublished' => $review->created_at->toDateString(),
        'name' => $review->title ?: null,
        'reviewBody' => $review->body ?: null,
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => (string) $review->rating,
            'bestRating' => '5',
            'worstRating' => '1',
        ],
    ], fn ($value) => $value !== null))->values()->all();

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
        'aggregateRating' => $aggregateRating,
        'review' => $reviewMarkup,
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
