<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminExpenseController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $expenses = $this->applySearch(
            Expense::query(),
            $request->input('search'),
            ['title', 'category', 'notes']
        )->orderBy('expense_date', 'desc')->paginate(20)->withQueryString();
        $totalAmount = Expense::sum('amount');

        return view('admin.expenses.index', compact('expenses', 'totalAmount'));
    }

    public function create()
    {
        $categories = ['Product Sourcing', 'Packing', 'Delivery', 'Marketing', 'Utilities', 'Other'];

        return view('admin.expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
            // The form always sends it; a script or an older integration may not.
            'paid_from' => ['nullable', Rule::in(PaymentAccounts::keys())],
        ]);

        $validated['paid_from'] = $validated['paid_from'] ?? PaymentAccounts::DEFAULT_PAYOUT;

        Expense::create($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense added successfully.');
    }

    public function edit(Expense $expense)
    {
        $categories = ['Product Sourcing', 'Packing', 'Delivery', 'Marketing', 'Utilities', 'Other'];

        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
            // The form always sends it; a script or an older integration may not.
            'paid_from' => ['nullable', Rule::in(PaymentAccounts::keys())],
        ]);

        $validated['paid_from'] = $validated['paid_from'] ?? PaymentAccounts::DEFAULT_PAYOUT;

        $expense->update($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete(
            $request, Expense::class
        );

        return $this->bulkResponse($result, 'expenses', 'admin.expenses.index');
    }
}
