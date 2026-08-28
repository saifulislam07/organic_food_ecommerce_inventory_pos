<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossReport;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $report = ProfitLossReport::between($request->input('from'), $request->input('to'));

        return view('admin.reports.profit-loss', [
            'report' => $report->summary(),
            'from' => $report->from(),
            'to' => $report->to(),
            'presets' => $this->presets(),
        ]);
    }

    /** The ranges people actually ask for. */
    private function presets(): array
    {
        return [
            'Today' => [now()->toDateString(), now()->toDateString()],
            'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Last month' => [
                now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'This year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
        ];
    }
}
