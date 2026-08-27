@extends('admin.layouts.app')

@section('title', 'New Purchase')
@section('page_title', 'Record New Stock Purchase')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('admin.purchases.store') }}" method="POST">
                    @csrf

                    <div
                        data-vue="PurchaseForm"
                        data-props="{{ json_encode([
                            'suppliers' => $suppliers,
                            'variants' => $variants,
                            'old' => (object) old(),
                            'errors' => $errors->toArray(),
                            'today' => date('Y-m-d'),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">Record Purchase &amp; Add Stock</button>
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
