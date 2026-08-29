@extends('admin.layouts.app')

@section('title', 'POS System')
@section('page_title', 'MohiPure Point of Sale')

@section('content')
    <div
        data-vue="PosApp"
        data-props="{{ json_encode([
            'items' => $items,
            'searchUrl' => route('admin.pos.search'),
            'storeUrl' => route('admin.pos.store'),
            'paymentMethods' => \App\Support\PaymentAccounts::options(),
            'defaultPaymentMethod' => \App\Support\PaymentAccounts::DEFAULT_POS,
        ], JSON_UNESCAPED_UNICODE) }}"
    ></div>
@endsection
