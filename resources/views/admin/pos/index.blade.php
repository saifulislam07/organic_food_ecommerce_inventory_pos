@extends('admin.layouts.app')

@section('title', 'POS System')
@section('page_title', 'Point of Sale')

@push('styles')
<style>
    /*
        A counter screen, not a document: the page itself never scrolls, and the
        two panes scroll independently inside it. Pushed from this view rather
        than living in the admin stylesheet, so it reaches only the POS.
    */
    body { overflow: hidden; }

    .admin-content {
        padding: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .admin-content > .admin-topbar {
        margin: 0;
        border-radius: 0;
        flex: 0 0 auto;
        border-bottom: 1px solid var(--gray-200);
        box-shadow: none;
    }
    /* Only the POS island stretches; the dialog host sits beside it at no height. */
    .admin-content > [data-vue="PosApp"] {
        flex: 1 1 auto;
        min-height: 0;
    }
    .admin-content > [data-vue="AdminDialogs"] { flex: 0 0 auto; }

    @media (max-width: 991.98px) {
        /* Below the desktop breakpoint the sidebar is a drawer and the panes
           stack, so the page goes back to scrolling normally. */
        body { overflow: auto; }
        .admin-content { height: auto; display: block; }
    }
</style>
@endpush

@section('content')
    <div
        data-vue="PosApp"
        data-props="{{ json_encode([
            'items' => $items,
            'categories' => $categories,
            'searchUrl' => route('admin.pos.search'),
            'customerUrl' => route('admin.pos.customers'),
            'storeUrl' => route('admin.pos.store'),
            'paymentMethods' => \App\Support\PaymentAccounts::options(),
            'defaultPaymentMethod' => \App\Support\PaymentAccounts::DEFAULT_POS,
            'sources' => collect(\App\Models\Order::POS_SOURCES)
                ->map(fn ($key) => ['value' => $key, 'label' => \App\Models\Order::SOURCES[$key]])
                ->values(),
        ], JSON_UNESCAPED_UNICODE) }}"
    ></div>
@endsection
