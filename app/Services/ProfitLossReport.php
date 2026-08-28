<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
use App\Support\PaymentAccounts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the shop earned and what it spent, between two dates.
 *
 * Two separate stories are told, because mixing them is the usual way these
 * reports end up wrong:
 *
 *   Profit and loss  revenue − cost of goods sold − expenses − damage.
 *                    A purchase is NOT a cost here: it turns cash into stock,
 *                    and only becomes a cost when that stock is sold.
 *
 *   Cash movement    money in and out per account head. A purchase IS counted
 *                    here, because the cash really left.
 */
class ProfitLossReport
{
    /** Orders in these states count as earned. */
    public const EARNED = ['delivered', 'shipped', 'processing', 'confirmed'];

    /** Adjustments that destroy stock rather than move it. */
    public const LOSSES = ['lost', 'damage'];

    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public static function between(?string $from, ?string $to): self
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        // A range typed backwards should still produce a report.
        return $start->greaterThan($end)
            ? new self($end->copy()->startOfDay(), $start->copy()->endOfDay())
            : new self($start, $end);
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function to(): Carbon
    {
        return $this->to;
    }

    /** Everything the page renders, in one call. */
    public function summary(): array
    {
        $sales = $this->sales();
        $expenses = $this->expenses();
        $damage = $this->damage();
        $purchases = $this->purchases();

        $grossProfit = $sales['revenue'] - $sales['cost'];
        $netProfit = $grossProfit - $expenses['total'] - $damage['total'];

        return [
            'orders' => $sales['orders'],
            'revenue' => $sales['revenue'],
            'delivery' => $sales['delivery'],
            'discount' => $sales['discount'],
            'cost_of_goods' => $sales['cost'],
            'gross_profit' => $grossProfit,
            'gross_margin' => $sales['revenue'] > 0 ? $grossProfit / $sales['revenue'] * 100 : 0.0,
            'expenses' => $expenses['total'],
            'expenses_by_category' => $expenses['by_category'],
            'damage' => $damage['total'],
            'damage_units' => $damage['units'],
            'net_profit' => $netProfit,
            'net_margin' => $sales['revenue'] > 0 ? $netProfit / $sales['revenue'] * 100 : 0.0,
            'purchases' => $purchases['total'],
            'accounts' => $this->accounts($purchases['by_account'], $expenses['by_account'], $sales['by_account']),
        ];
    }

    /**
     * Revenue and what those goods cost us.
     *
     * The cost comes from the variant's cost_price. A variant with no cost
     * recorded contributes nothing, which flatters the margin — the report says
     * so rather than guessing a number.
     */
    private function sales(): array
    {
        $totals = Order::query()
            ->whereIn('status', self::EARNED)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(delivery_charge), 0) as delivery')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->first();

        $cost = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereIn('orders.status', self::EARNED)
            ->whereBetween('orders.created_at', [$this->from, $this->to])
            ->sum(DB::raw('order_items.quantity * COALESCE(product_variants.cost_price, 0)'));

        $byAccount = Order::query()
            ->whereIn('status', self::EARNED)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->groupBy('payment_method')
            ->pluck(DB::raw('COALESCE(SUM(total), 0)'), 'payment_method')
            ->all();

        return [
            'orders' => (int) $totals->orders,
            'revenue' => (float) $totals->revenue,
            'delivery' => (float) $totals->delivery,
            'discount' => (float) $totals->discount,
            'cost' => (float) $cost,
            'by_account' => $byAccount,
        ];
    }

    private function expenses(): array
    {
        $rows = Expense::query()
            ->whereBetween('expense_date', [$this->from->toDateString(), $this->to->toDateString()]);

        return [
            'total' => (float) (clone $rows)->sum('amount'),
            'by_category' => (clone $rows)->groupBy('category')
                ->pluck(DB::raw('COALESCE(SUM(amount), 0)'), 'category')
                ->all(),
            'by_account' => (clone $rows)->groupBy('paid_from')
                ->pluck(DB::raw('COALESCE(SUM(amount), 0)'), 'paid_from')
                ->all(),
        ];
    }

    /** Stock written off, valued at what it cost us. */
    private function damage(): array
    {
        $rows = Adjustment::query()
            ->leftJoin('product_variants', 'product_variants.id', '=', 'adjustments.product_variant_id')
            ->whereIn('adjustments.type', self::LOSSES)
            ->whereBetween('adjustments.adjustment_date', [$this->from->toDateString(), $this->to->toDateString()]);

        return [
            'total' => (float) (clone $rows)->sum(
                DB::raw('adjustments.quantity * COALESCE(product_variants.cost_price, 0)')
            ),
            'units' => (int) (clone $rows)->sum('adjustments.quantity'),
        ];
    }

    private function purchases(): array
    {
        $rows = Purchase::query()
            ->whereBetween('purchase_date', [$this->from->toDateString(), $this->to->toDateString()]);

        return [
            'total' => (float) (clone $rows)->sum(DB::raw('purchase_price * quantity')),
            'by_account' => (clone $rows)->groupBy('paid_from')
                ->pluck(DB::raw('COALESCE(SUM(purchase_price * quantity), 0)'), 'paid_from')
                ->all(),
        ];
    }

    /**
     * Money in and out, per account head, with every head present so the table
     * keeps the same shape whatever happened this month.
     */
    private function accounts(array $purchases, array $expenses, array $sales): array
    {
        $heads = [];

        foreach (PaymentAccounts::keys() as $key) {
            $in = (float) ($sales[$key] ?? 0);
            $out = (float) ($purchases[$key] ?? 0) + (float) ($expenses[$key] ?? 0);

            $heads[$key] = [
                'label' => PaymentAccounts::label($key),
                'colour' => PaymentAccounts::colour($key),
                'icon' => PaymentAccounts::icon($key),
                'in' => $in,
                'out' => $out,
                'net' => $in - $out,
            ];
        }

        // A head that was retired still has history; show it rather than lose it.
        foreach (array_merge(array_keys($sales), array_keys($purchases), array_keys($expenses)) as $key) {
            if ($key === null || $key === '' || isset($heads[$key])) {
                continue;
            }

            $in = (float) ($sales[$key] ?? 0);
            $out = (float) ($purchases[$key] ?? 0) + (float) ($expenses[$key] ?? 0);

            $heads[$key] = [
                'label' => PaymentAccounts::label($key),
                'colour' => 'dark',
                'icon' => 'bi-wallet2',
                'in' => $in,
                'out' => $out,
                'net' => $in - $out,
            ];
        }

        return $heads;
    }
}
