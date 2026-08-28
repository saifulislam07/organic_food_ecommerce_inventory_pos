@extends('admin.layouts.app')

@section('title', 'Profit & Loss')
@section('page_title', 'Profit & Loss')

@php
    $money = fn ($n) => '৳'.number_format((float) $n, 0);
    $signed = fn ($n) => ((float) $n < 0 ? '−' : '').'৳'.number_format(abs((float) $n), 0);
@endphp

@section('content')

{{-- Range --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.reports.profitLoss') }}" class="row g-2 align-items-end">
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small fw-bold text-muted mb-1">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
            </div>
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small fw-bold text-muted mb-1">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
            </div>
            <div class="col-sm-4 col-lg-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
            </div>
            <div class="col-lg-4 text-lg-end">
                @foreach($presets as $label => [$start, $end])
                    <a href="{{ route('admin.reports.profitLoss', ['from' => $start, 'to' => $end]) }}"
                       class="btn btn-sm btn-outline-secondary mb-1">{{ $label }}</a>
                @endforeach
            </div>
        </form>
    </div>
</div>

{{-- The bottom line --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1">Revenue</div>
                <div class="h4 fw-bold text-dark mb-0">{{ $money($report['revenue']) }}</div>
                <div class="small text-muted">{{ $report['orders'] }} order(s)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1">Gross profit</div>
                <div class="h4 fw-bold text-primary mb-0">{{ $signed($report['gross_profit']) }}</div>
                <div class="small text-muted">{{ number_format($report['gross_margin'], 1) }}% margin</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1">Costs</div>
                <div class="h4 fw-bold text-danger mb-0">
                    {{ $money($report['cost_of_goods'] + $report['expenses'] + $report['damage']) }}
                </div>
                <div class="small text-muted">goods, expenses, damage</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 {{ $report['net_profit'] < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1">Net profit</div>
                <div class="h4 fw-bold mb-0 {{ $report['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                    {{ $signed($report['net_profit']) }}
                </div>
                <div class="small text-muted">{{ number_format($report['net_margin'], 1) }}% of revenue</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Working --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">How it adds up</h5>
                <small class="text-muted">
                    {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                </small>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="ps-4">Sales revenue</td>
                            <td class="text-end pe-4 fw-bold text-dark">{{ $money($report['revenue']) }}</td>
                        </tr>
                        <tr class="text-muted small">
                            <td class="ps-5">of which delivery charges</td>
                            <td class="text-end pe-4">{{ $money($report['delivery']) }}</td>
                        </tr>
                        <tr class="text-muted small">
                            <td class="ps-5">discounts given</td>
                            <td class="text-end pe-4">−{{ $money($report['discount']) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Cost of goods sold</td>
                            <td class="text-end pe-4 text-danger">−{{ $money($report['cost_of_goods']) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="ps-4 fw-bold">Gross profit</td>
                            <td class="text-end pe-4 fw-bold">{{ $signed($report['gross_profit']) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Expenses</td>
                            <td class="text-end pe-4 text-danger">−{{ $money($report['expenses']) }}</td>
                        </tr>
                        @foreach($report['expenses_by_category'] as $category => $amount)
                            <tr class="text-muted small">
                                <td class="ps-5">{{ ucfirst($category ?: 'uncategorised') }}</td>
                                <td class="text-end pe-4">−{{ $money($amount) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="ps-4">
                                Damage &amp; loss
                                @if($report['damage_units'])
                                    <span class="text-muted small">({{ $report['damage_units'] }} unit(s))</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 text-danger">−{{ $money($report['damage']) }}</td>
                        </tr>
                        <tr class="{{ $report['net_profit'] < 0 ? 'table-danger' : 'table-success' }}">
                            <td class="ps-4 fw-bold">Net profit</td>
                            <td class="text-end pe-4 fw-bold">{{ $signed($report['net_profit']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                Stock bought in this period cost {{ $money($report['purchases']) }}. That is cash spent, not a loss —
                it becomes a cost above only once the stock is sold.
            </div>
        </div>
    </div>

    {{-- Accounts --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">Where the money moved</h5>
                <small class="text-muted">Sales in, purchases and expenses out</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Account</th>
                                <th class="text-end">In</th>
                                <th class="text-end">Out</th>
                                <th class="text-end pe-4">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalIn = 0; $totalOut = 0; @endphp
                            @foreach($report['accounts'] as $key => $head)
                                @php $totalIn += $head['in']; $totalOut += $head['out']; @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-{{ $head['colour'] }}-subtle text-{{ $head['colour'] }}">
                                            <i class="bi {{ $head['icon'] }}"></i> {{ $head['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-end {{ $head['in'] ? 'text-success' : 'text-muted' }}">
                                        {{ $money($head['in']) }}
                                    </td>
                                    <td class="text-end {{ $head['out'] ? 'text-danger' : 'text-muted' }}">
                                        {{ $money($head['out']) }}
                                    </td>
                                    <td class="text-end pe-4 fw-bold {{ $head['net'] < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ $signed($head['net']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th class="ps-4">Total</th>
                                <th class="text-end">{{ $money($totalIn) }}</th>
                                <th class="text-end">{{ $money($totalOut) }}</th>
                                <th class="text-end pe-4">{{ $signed($totalIn - $totalOut) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
