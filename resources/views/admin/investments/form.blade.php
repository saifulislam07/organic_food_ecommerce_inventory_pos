@extends('admin.layouts.app')

@php $record = $investment ?? null; @endphp

@section('title', $record ? 'Edit Investment' : 'Record Investment')
@section('page_title', $record ? 'Edit Investment' : 'Record an Investment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        @if($investors->isEmpty())
            <div class="alert alert-warning">
                No active investors yet.
                <a href="{{ route('admin.investors.create') }}" class="alert-link">Add one first</a> —
                money in has to belong to somebody.
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ $record ? route('admin.investments.update', $record) : route('admin.investments.store') }}"
                      method="POST">
                    @csrf
                    @if($record) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Investor</label>
                            <select name="investor_id" class="form-select @error('investor_id') is-invalid @enderror" required>
                                <option value="">Select investor</option>
                                @foreach($investors as $investor)
                                    <option value="{{ $investor->id }}"
                                            @selected((string) old('investor_id', $record->investor_id ?? '') === (string) $investor->id)>
                                        {{ $investor->name }}{{ $investor->is_active ? '' : ' (inactive)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('investor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Amount (৳)</label>
                            <input type="number" step="0.01" min="0.01" name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', $record->amount ?? '') }}" placeholder="0.00" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Date</label>
                            <input type="date" name="invested_at"
                                   class="form-control @error('invested_at') is-invalid @enderror"
                                   value="{{ old('invested_at', $record?->invested_at?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            @error('invested_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.account-select', [
                                'name' => 'received_in',
                                'label' => 'Received in',
                                'selected' => $record->received_in ?? null,
                                'help' => 'Which account the money landed in. Shown in Profit & Loss.',
                            ])
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="e.g., second instalment for the winter clothing stock">{{ old('notes', $record->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0 small text-muted">
                        <i class="bi bi-info-circle"></i>
                        Capital coming in is not income, so this never touches the profit figure.
                        It does move the account balance in the Profit &amp; Loss report.
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4" @disabled($investors->isEmpty())>
                            {{ $record ? 'Update Investment' : 'Save Investment' }}
                        </button>
                        <a href="{{ route('admin.investments.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
