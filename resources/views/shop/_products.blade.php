@foreach($products as $product)
    <div class="col-xl-4 col-md-6 col-6">
        @include('partials.product-card', ['product' => $product])
    </div>
@endforeach
