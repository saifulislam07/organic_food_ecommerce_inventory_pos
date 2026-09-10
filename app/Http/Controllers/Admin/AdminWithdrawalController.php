<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Withdrawal;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Money going back out to an investor.
 *
 * Capital out, not a cost: it leaves the profit figure alone and shows in the
 * account table instead. Taking out more than was put in is allowed — an owner
 * drawing this month's profit is the ordinary case — so the form says what it
 * has noticed and saves anyway.
 */
class AdminWithdrawalController extends Controller
{
    use BulkDeletes, SearchesRecords;
    use SortsRecords;

    public function index(Request $request)
    {
        $withdrawals = $this->applySearch(
            Withdrawal::with('investor'),
            $request->input('search'),
            ['notes', 'investor.name', 'investor.phone']
        );

        $this->applySort($withdrawals, $request, [
            'withdrawn_at' => 'withdrawn_at',
            'investor' => 'investor_id',
            'account' => 'paid_from',
            'amount' => 'amount',
        ], 'withdrawn_at');

        $withdrawals = $withdrawals->paginate(20)->withQueryString();

        return view('admin.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'totalAmount' => (float) Withdrawal::sum('amount'),
        ]);
    }

    public function create()
    {
        return view('admin.withdrawals.create', $this->formData());
    }

    public function store(Request $request)
    {
        $withdrawal = Withdrawal::create($this->validated($request));

        return redirect()->route('admin.withdrawals.index')
            ->with('success', 'Withdrawal recorded.')
            ->with($this->overdrawWarning($withdrawal));
    }

    public function edit(Withdrawal $withdrawal)
    {
        return view('admin.withdrawals.edit', $this->formData($withdrawal) + compact('withdrawal'));
    }

    public function update(Request $request, Withdrawal $withdrawal)
    {
        $withdrawal->update($this->validated($request));

        return redirect()->route('admin.withdrawals.index')
            ->with('success', 'Withdrawal updated.')
            ->with($this->overdrawWarning($withdrawal->fresh()));
    }

    public function destroy(Withdrawal $withdrawal)
    {
        $withdrawal->delete();

        return redirect()->route('admin.withdrawals.index')->with('success', 'Withdrawal deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkResponse(
            $this->bulkDelete($request, Withdrawal::class),
            'withdrawals',
            'admin.withdrawals.index'
        );
    }

    /**
     * Said after the fact rather than enforced before it.
     *
     * The balance is only negative because profit has been taken out, which is
     * a thing that happens and not a thing to block. Worth pointing at, though,
     * because it is also what a typed extra zero looks like.
     *
     * @return array<string, string>
     */
    private function overdrawWarning(Withdrawal $withdrawal): array
    {
        $investor = $withdrawal->investor;
        $balance = $investor->balance();

        if ($balance >= 0) {
            return [];
        }

        return ['warning' => sprintf(
            '%s has now taken out ৳%s more than they put in.',
            $investor->name,
            number_format(abs($balance), 2)
        )];
    }

    private function formData(?Withdrawal $withdrawal = null): array
    {
        return [
            'investors' => Investor::query()
                ->where(fn ($q) => $q->active()->orWhereKey($withdrawal?->investor_id))
                ->sorted()
                ->withSum('investments', 'amount')
                ->withSum('withdrawals', 'amount')
                ->get(['id', 'name', 'is_active']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'investor_id' => ['required', 'exists:investors,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'withdrawn_at' => ['required', 'date'],
            'paid_from' => ['nullable', Rule::in(PaymentAccounts::keys())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'investor_id.required' => 'Choose who the money went to.',
            'amount.min' => 'A withdrawal of nothing is not a record worth keeping.',
        ]);

        $data['paid_from'] = $data['paid_from'] ?? PaymentAccounts::DEFAULT_PAYOUT;

        return $data;
    }
}
