{{--
    Which account the money moved through. Drives the Profit & Loss report.

    @include('admin.partials.account-select', ['name' => 'paid_from', 'selected' => $expense->paid_from ?? null])
--}}
@php
    $field = $name ?? 'paid_from';
    $current = old($field, $selected ?? \App\Support\PaymentAccounts::DEFAULT_PAYOUT);
@endphp

<label class="form-label fw-bold">{{ $label ?? 'Paid from' }} *</label>
<select name="{{ $field }}" class="form-select @error($field) is-invalid @enderror" required>
    @foreach(\App\Support\PaymentAccounts::HEADS as $key => $head)
        <option value="{{ $key }}" @selected($current === $key)>{{ $head[0] }} — {{ $head[1] }}</option>
    @endforeach
</select>
@error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
<div class="form-text">{{ $help ?? 'Shown in the Profit & Loss report.' }}</div>
