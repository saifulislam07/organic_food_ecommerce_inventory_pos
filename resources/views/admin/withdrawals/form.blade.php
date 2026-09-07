@extends('admin.layouts.app')

@php
    $record = $withdrawal ?? null;

    /*
        What each investor is holding right now, so the form can say what a
        withdrawal would do before it is saved. Never a block — an owner drawing
        this month's profit is the ordinary case, and refusing it would make the
        ledger lie about what actually happened.
    */
    $balances = $investors->mapWithKeys(fn ($investor) => [$investor->id => $investor->balance()]);
@endphp

@section('title', $record ? 'Edit Withdrawal' : 'Record Withdrawal')
@section('page_title', $record ? 'Edit Withdrawal' : 'Record a Withdrawal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        @if($investors->isEmpty())
            <div class="alert alert-warning">
                No active investors yet.
                <a href="{{ route('admin.investors.create') }}" class="alert-link">Add one first</a> —
                money out has to go to somebody.
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ $record ? route('admin.withdrawals.update', $record) : route('admin.withdrawals.store') }}"
                      method="POST" data-withdraw-form
                      data-balances="{{ json_encode($balances) }}">
                    @csrf
                    @if($record) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Investor</label>
                            <select name="investor_id" class="form-select @error('investor_id') is-invalid @enderror"
                                    data-withdraw-investor required>
                                <option value="">Select investor</option>
                                @foreach($investors as $investor)
                                    <option value="{{ $investor->id }}"
                                            @selected((string) old('investor_id', $record->investor_id ?? '') === (string) $investor->id)>
                                        {{ $investor->name }}{{ $investor->is_active ? '' : ' (inactive)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('investor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text" data-withdraw-balance></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Amount (৳)</label>
                            <input type="number" step="0.01" min="0.01" name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', $record->amount ?? '') }}" placeholder="0.00"
                                   data-withdraw-amount required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Date</label>
                            <input type="date" name="withdrawn_at"
                                   class="form-control @error('withdrawn_at') is-invalid @enderror"
                                   value="{{ old('withdrawn_at', $record?->withdrawn_at?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            @error('withdrawn_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.account-select', [
                                'name' => 'paid_from',
                                'label' => 'Paid from',
                                'selected' => $record->paid_from ?? null,
                                'help' => 'Which account the money left. Shown in Profit & Loss.',
                            ])
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="e.g., monthly profit draw">{{ old('notes', $record->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0 d-none" data-withdraw-warning></div>

                    <div class="alert alert-light border mt-3 mb-0 small text-muted">
                        <i class="bi bi-info-circle"></i>
                        Capital going out is not a cost, so this never touches the profit figure.
                        It does move the account balance in the Profit &amp; Loss report.
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4" @disabled($investors->isEmpty())>
                            {{ $record ? 'Update Withdrawal' : 'Save Withdrawal' }}
                        </button>
                        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/*
    Tells the admin what this withdrawal does to the balance, and warns when it
    goes past what the investor put in. It never stops the save: taking profit
    out is normal, and blocking it would only push the entry out of the ledger
    and into somebody's head.
*/
(function () {
    const form = document.querySelector('[data-withdraw-form]');

    if (! form) {
        return;
    }

    const investor = form.querySelector('[data-withdraw-investor]');
    const amount = form.querySelector('[data-withdraw-amount]');
    const balanceNote = form.querySelector('[data-withdraw-balance]');
    const warning = form.querySelector('[data-withdraw-warning]');
    const balances = JSON.parse(form.dataset.balances || '{}');

    const taka = (value) => '৳' + Math.abs(value).toLocaleString('en-US', {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
    });

    function check() {
        const held = balances[investor.value];

        if (held === undefined) {
            balanceNote.textContent = '';
            warning.classList.add('d-none');

            return;
        }

        balanceNote.textContent = held < 0
            ? 'Already drawn ' + taka(held) + ' beyond what they put in.'
            : 'Currently holding ' + taka(held) + '.';

        const asked = parseFloat(amount.value || '0');
        const left = held - asked;

        warning.classList.toggle('d-none', ! (asked > 0 && left < 0));

        if (asked > 0 && left < 0) {
            warning.textContent = 'This takes ' + taka(left)
                + ' more than they put in. That is fine if it is profit being drawn — it will still save.';
        }
    }

    investor.addEventListener('change', check);
    amount.addEventListener('input', check);
    check();
})();
</script>
@endpush
