@foreach($products as $product)
    {{-- Four across beside the filter sidebar, matching the front page's density. --}}
    <div class="col-xl-3 col-lg-4 col-md-4 col-6">
        @include('partials.product-card', ['product' => $product])
    </div>
@endforeach
