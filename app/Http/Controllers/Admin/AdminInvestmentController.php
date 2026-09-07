<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Money coming in from an investor.
 *
 * Parallel to the expense ledger on purpose — same shape, same account picker —
 * but a separate table, because capital in is not income and must never reach
 * the profit figure.
 */
class AdminInvestmentController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $investments = $this->applySearch(
            Investment::with('investor'),
            $request->input('search'),
            ['notes', 'investor.name', 'investor.phone']
        )
            ->orderBy('invested_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.investments.index', [
            'investments' => $investments,
            'totalAmount' => (float) Investment::sum('amount'),
        ]);
    }

    public function create()
    {
        return view('admin.investments.create', $this->formData());
    }

    public function store(Request $request)
    {
        Investment::create($this->validated($request));

        return redirect()->route('admin.investments.index')->with('success', 'Investment recorded.');
    }

    public function edit(Investment $investment)
    {
        return view('admin.investments.edit', $this->formData($investment) + compact('investment'));
    }

    public function update(Request $request, Investment $investment)
    {
        $investment->update($this->validated($request));

        return redirect()->route('admin.investments.index')->with('success', 'Investment updated.');
    }

    public function destroy(Investment $investment)
    {
        $investment->delete();

        return redirect()->route('admin.investments.index')->with('success', 'Investment deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkResponse(
            $this->bulkDelete($request, Investment::class),
            'investments',
            'admin.investments.index'
        );
    }

    /**
     * Inactive investors are still offered when they are the one on this row,
     * or editing an old record would silently reassign it.
     */
    private function formData(?Investment $investment = null): array
    {
        return [
            'investors' => Investor::query()
                ->where(fn ($q) => $q->active()->orWhereKey($investment?->investor_id))
                ->sorted()
                ->get(['id', 'name', 'is_active']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'investor_id' => ['required', 'exists:investors,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'invested_at' => ['required', 'date'],
            'received_in' => ['nullable', Rule::in(PaymentAccounts::keys())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'investor_id.required' => 'Choose who the money came from.',
            'amount.min' => 'An investment of nothing is not a record worth keeping.',
        ]);

        $data['received_in'] = $data['received_in'] ?? PaymentAccounts::DEFAULT_PAYOUT;

        return $data;
    }
}
