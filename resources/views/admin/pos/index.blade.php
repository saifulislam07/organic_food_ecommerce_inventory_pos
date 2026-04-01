@extends('admin.layouts.app')

@section('title', 'POS System')
@section('page_title', 'Mango Hut Point of Sale')

@push('styles')
<style>
    .pos-product-card {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .pos-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        border-color: #2d6a4f;
    }
    .pos-cart-container {
        height: calc(100vh - 250px);
        overflow-y: auto;
    }
    .search-results {
        position: absolute;
        width: 100%;
        z-index: 1050;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Product Selection Area -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="position-relative mb-4">
                    <div class="input-group input-group-lg border rounded-pill overflow-hidden bg-light">
                        <span class="input-group-text bg-transparent border-0 ps-4">
                            <i class="bi bi-search text-primary"></i>
                        </span>
                        <input type="text" id="posSearch" class="form-control bg-transparent border-0" placeholder="Search product by name or SKU...">
                    </div>
                    <div id="searchResults" class="list-group search-results d-none mt-2"></div>
                </div>

                <div class="row g-3" id="productsGrid">
                    @foreach($products as $product)
                        @foreach($product->variants as $variant)
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 pos-product-card shadow-sm" 
                                 onclick="addToCart({{ $variant->id }}, '{{ $product->name }}', '{{ $variant->name }}', {{ $variant->sale_price ?? $variant->price }}, {{ $variant->stock }}, '{{ $product->image_url }}')">
                                <img src="{{ $product->image_url }}" class="card-img-top p-2 rounded" style="height: 120px; object-fit: cover;">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-1 fw-bold text-truncate small">{{ $product->name }}</h6>
                                    <small class="text-muted d-block mb-2" style="font-size: 0.75rem;">{{ $variant->name }}</small>
                                    <div class="d-flex justify-content-between align-items-center bg-light rounded px-2 py-1">
                                        <span class="fw-bold text-primary small">৳{{ number_format($variant->sale_price ?? $variant->price) }}</span>
                                        <small class="badge bg-white border {{ $variant->stock < 5 ? 'text-danger' : 'text-success' }}" style="font-size: 0.65rem;">{{ $variant->stock }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Basket Area -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold text-dark">Current Order</h5>
            </div>
            <div class="card-body pos-cart-container pt-0">
                <div id="cartItems" class="list-group list-group-flush mb-4">
                    <div class="text-center py-5 text-muted empty-cart-msg">
                        <i class="bi bi-cart3 fs-1 d-block mb-2 opacity-25"></i>
                        Basket is empty
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-4">
                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Subtotal:</span>
                    <span id="subtotal">৳0</span>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small text-muted mb-1">Delivery:</label>
                        <input type="number" id="deliveryCharge" class="form-control form-control-sm" value="0" onchange="updateTotals()">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-1">Discount (৳):</label>
                        <input type="number" id="discountAmount" class="form-control form-control-sm" value="0" onchange="updateTotals()">
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Total:</h5>
                    <h5 class="fw-bold text-primary mb-0" id="grandTotal">৳0</h5>
                </div>

                <button class="btn btn-primary w-100 py-3 fw-bold shadow-sm mb-3" onclick="showCheckoutModal()" id="btnCheckout" disabled>
                    PROCESS ORDER <i class="bi bi-arrow-right ms-2"></i>
                </button>
                <button class="btn btn-outline-danger w-100 btn-sm border-0" onclick="clearCart()">Clear Basket</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Customer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="posOrderForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" value="Walk-in Customer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="customer_phone" class="form-control" required placeholder="01XXXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Delivery Address</label>
                        <textarea name="customer_address" class="form-control" rows="3" required>Shop Counter</textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold" id="btnSubmitOrder">
                        CONFIRM & PRINT <i class="bi bi-printer ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let cart = [];

    function addToCart(variantId, pName, vName, price, stock, image) {
        if (stock <= 0) {
            alert('Out of stock!');
            return;
        }

        const existing = cart.find(i => i.variantId === variantId);
        if (existing) {
            if (existing.quantity >= stock) {
                alert('Cannot exceed available stock!');
                return;
            }
            existing.quantity++;
        } else {
            cart.push({
                variantId, pName, vName, price, image, stock, quantity: 1
            });
        }
        renderCart();
    }

    function updateQty(variantId, change) {
        const item = cart.find(i => i.variantId === variantId);
        if (item) {
            if (item.quantity + change > item.stock) {
                alert('Insufficient stock!');
                return;
            }
            item.quantity += change;
            if (item.quantity <= 0) {
                removeFromCart(variantId);
            } else {
                renderCart();
            }
        }
    }

    function removeFromCart(variantId) {
        cart = cart.filter(i => i.variantId !== variantId);
        renderCart();
    }

    function clearCart() {
        if (confirm('Clear entire basket?')) {
            cart = [];
            renderCart();
        }
    }

    function renderCart() {
        const container = $('#cartItems');
        container.empty();

        if (cart.length === 0) {
            container.append('<div class="text-center py-5 text-muted empty-cart-msg"><i class="bi bi-cart3 fs-1 d-block mb-2 opacity-25"></i>Basket is empty</div>');
            $('#btnCheckout').prop('disabled', true);
        } else {
            $('#btnCheckout').prop('disabled', false);
            cart.forEach(item => {
                container.append(`
                    <div class="list-group-item bg-transparent border-0 px-0 mb-3">
                        <div class="d-flex gap-3">
                            <img src="${item.image}" class="rounded" width="50" height="50" style="object-fit:cover;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0 fw-bold small">${item.pName}</h6>
                                        <small class="text-muted" style="font-size:0.7rem;">${item.vName}</small>
                                    </div>
                                    <button class="btn btn-sm text-danger p-0 border-0" onclick="removeFromCart(${item.variantId})"><i class="bi bi-trash"></i></button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="input-group input-group-sm" style="width: 110px;">
                                        <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="updateQty(${item.variantId}, -1)">-</button>
                                        <input type="text" class="form-control text-center bg-white border-0 fw-bold" value="${item.quantity}" readonly>
                                        <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="updateQty(${item.variantId}, 1)">+</button>
                                    </div>
                                    <span class="fw-bold text-dark">৳${(item.price * item.quantity).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
        }
        updateTotals();
    }

    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const delivery = parseFloat($('#deliveryCharge').val()) || 0;
        const discount = parseFloat($('#discountAmount').val()) || 0;
        $('#subtotal').text('৳' + subtotal.toLocaleString());
        $('#grandTotal').text('৳' + (subtotal + delivery - discount).toLocaleString());
    }

    function showCheckoutModal() {
        $('#checkoutModal').modal('show');
    }

    $('#posOrderForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitOrder');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        const data = {
            _token: '{{ csrf_token() }}',
            customer_name: $('input[name="customer_name"]').val(),
            customer_phone: $('input[name="customer_phone"]').val(),
            customer_address: $('textarea[name="customer_address"]').val(),
            delivery_charge: $('#deliveryCharge').val(),
            discount_amount: $('#discountAmount').val(),
            items: cart.map(i => ({ variant_id: i.variantId, quantity: i.quantity }))
        };

        $.ajax({
            url: '{{ route("admin.pos.store") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                alert('Error: ' + msg);
            }
        });
    });

    // Real-time Search
    $('#posSearch').on('input', function() {
        const q = $(this).val();
        if (q.length < 2) {
            $('#searchResults').addClass('d-none');
            return;
        }

        $.get('{{ route("admin.pos.search") }}', { q: q }, function(data) {
            const container = $('#searchResults');
            container.empty().removeClass('d-none');
            
            if (data.length === 0) {
                container.append('<div class="list-group-item text-center py-3 text-muted">No products found</div>');
            } else {
                data.forEach(v => {
                    container.append(`
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3" 
                           onclick="addToCart(${v.id}, '${v.product.name}', '${v.name}', ${v.sale_price ?? v.price}, ${v.stock}, '${v.product.image_url}')">
                            <img src="${v.product.image_url}" class="rounded" width="40" height="40">
                            <div>
                                <h6 class="mb-0 fw-bold small">${v.product.name}</h6>
                                <small class="text-muted" style="font-size: 0.7rem;">${v.name} — SKU: ${v.sku || 'N/A'}</small>
                            </div>
                            <div class="ms-auto text-end">
                                <span class="fw-bold d-block small">৳${(v.sale_price ?? v.price).toLocaleString()}</span>
                                <small class="badge ${v.stock < 10 ? 'bg-danger' : 'bg-success'}" style="font-size:0.6rem;">${v.stock} left</small>
                            </div>
                        </a>
                    `);
                });
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#posSearch').length && !$(e.target).closest('#searchResults').length) {
            $('#searchResults').addClass('d-none');
        }
    });

</script>
@endpush
