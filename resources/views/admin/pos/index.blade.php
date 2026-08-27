@extends('admin.layouts.app')

@section('title', 'POS System')
@section('page_title', 'Mango Hut Point of Sale')

@section('content')
    <div
        data-vue="PosApp"
        data-props="{{ json_encode([
            'items' => $items,
            'searchUrl' => route('admin.pos.search'),
            'storeUrl' => route('admin.pos.store'),
        ], JSON_UNESCAPED_UNICODE) }}"
    ></div>
@endsection
