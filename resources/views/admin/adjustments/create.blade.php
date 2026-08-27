@extends('admin.layouts.app')

@section('title', 'New Adjustment')
@section('page_title', 'Record Stock Adjustment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="alert alert-info py-2 small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Adjustments help you track stock that is no longer sellable (Damage/Lost) or stock that has been returned to inventory.
                </div>

                <form action="{{ route('admin.adjustments.store') }}" method="POST">
                    @csrf

                    <div
                        data-vue="AdjustmentForm"
                        data-props="{{ json_encode([
                            'variants' => $variants,
                            'old' => (object) old(),
                            'errors' => $errors->toArray(),
                            'today' => date('Y-m-d'),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-warning px-4 fw-bold">Update Inventory</button>
                        <a href="{{ route('admin.adjustments.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
