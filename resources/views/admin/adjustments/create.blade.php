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
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark mb-1">Select Product Variant</label>
                            <select name="product_variant_id" class="form-select @error('product_variant_id') is-invalid @enderror" required>
                                <option value="">Select Product & Variant</option>
                                @foreach($variants as $variant)
                                    <option value="{{ $variant->id }}" {{ old('product_variant_id') == $variant->id ? 'selected' : '' }}>
                                        {{ $variant->product->name }} — {{ $variant->name }} (Current Stock: {{ $variant->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_variant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark mb-1">Adjustment Type</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="damage" {{ old('type') == 'damage' ? 'selected' : '' }}>Damage (Decreases Stock)</option>
                                <option value="lost" {{ old('type') == 'lost' ? 'selected' : '' }}>Lost (Decreases Stock)</option>
                                <option value="returned" {{ old('type') == 'returned' ? 'selected' : '' }}>Returned (Increases Stock)</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other (Decreases Stock)</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark mb-1">Quantity</label>
                            <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="Enter amount" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark mb-1">Adjustment Date</label>
                            <input type="date" name="adjustment_date" class="form-control @error('adjustment_date') is-invalid @enderror" value="{{ old('adjustment_date', date('Y-m-d')) }}" required>
                            @error('adjustment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark mb-1">Reason/Notes</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Explain why this adjustment is being made..." required>{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

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
