<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

/**
 * The people behind the money: who they are, and where each one stands.
 */
class AdminInvestorController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $investors = $this->applySearch(
            Investor::query(),
            $request->input('search'),
            ['name', 'phone', 'email', 'notes']
        )
            // Summed in the query rather than per row: a list of twenty
            // investors would otherwise be forty extra queries.
            ->withSum('investments', 'amount')
            ->withSum('withdrawals', 'amount')
            ->sorted()
            ->paginate(20)
            ->withQueryString();

        return view('admin.investors.index', [
            'investors' => $investors,
            'totals' => [
                'invested' => (float) Investment::sum('amount'),
                'withdrawn' => (float) Withdrawal::sum('amount'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.investors.create', ['investor' => null]);
    }

    public function store(Request $request)
    {
        Investor::create($this->validated($request));

        return redirect()->route('admin.investors.index')->with('success', 'Investor added.');
    }

    /** One investor's standing, and every movement behind it. */
    public function show(Investor $investor)
    {
        $investor->load(['investments', 'withdrawals']);

        return view('admin.investors.show', [
            'investor' => $investor,
            'statement' => $investor->statement(),
        ]);
    }

    public function edit(Investor $investor)
    {
        return view('admin.investors.edit', compact('investor'));
    }

    public function update(Request $request, Investor $investor)
    {
        $investor->update($this->validated($request));

        return redirect()->route('admin.investors.index')->with('success', 'Investor updated.');
    }

    public function destroy(Investor $investor)
    {
        if ($reason = $this->blocksDeletion($investor)) {
            return back()->withErrors(['delete' => $reason]);
        }

        $investor->delete();

        return redirect()->route('admin.investors.index')->with('success', 'Investor deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Investor::class, fn ($investor) => $this->blocksDeletion($investor));

        return $this->bulkResponse($result, 'investors', 'admin.investors.index');
    }

    /**
     * Money that moved is the record of what happened. Switching an investor
     * off retires them from the pickers without throwing that away.
     */
    private function blocksDeletion(Investor $investor): ?string
    {
        if (! $investor->investments()->exists() && ! $investor->withdrawals()->exists()) {
            return null;
        }

        return "\"{$investor->name}\" has money recorded against them, so cannot be deleted. Mark them inactive instead.";
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
