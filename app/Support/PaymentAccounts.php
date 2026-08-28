<?php

namespace App\Support;

/**
 * Where the money sits.
 *
 * One list drives the POS payment picker, the expense and purchase forms, and
 * the account columns of the profit and loss report, so a new head cannot be
 * added to one and forgotten in the others.
 */
class PaymentAccounts
{
    /** key => [English, বাংলা, bootstrap colour, icon] */
    public const HEADS = [
        'cash' => ['Cash', 'ক্যাশ', 'success', 'bi-cash-stack'],
        'bank' => ['Bank', 'ব্যাংক', 'primary', 'bi-bank'],
        'bkash' => ['bKash', 'বিকাশ', 'danger', 'bi-phone'],
        'nagad' => ['Nagad', 'নগদ', 'warning', 'bi-phone'],
        'rocket' => ['Rocket', 'রকেট', 'info', 'bi-phone'],
        'cod' => ['Cash on Delivery', 'ক্যাশ অন ডেলিভারি', 'secondary', 'bi-truck'],
    ];

    /** What a website order is paid by until the rider hands the money in. */
    public const DEFAULT_ORDER = 'cod';

    /** What a sale over the counter is paid by unless the cashier says otherwise. */
    public const DEFAULT_POS = 'cash';

    /** What money leaves by unless someone picks another head. */
    public const DEFAULT_PAYOUT = 'cash';

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::HEADS);
    }

    public static function label(?string $key, ?string $locale = null): string
    {
        $head = self::HEADS[$key] ?? null;

        if (! $head) {
            return $key ? ucfirst($key) : '—';
        }

        return ($locale ?: app()->getLocale()) === 'bn' ? $head[1] : $head[0];
    }

    public static function colour(?string $key): string
    {
        return self::HEADS[$key][2] ?? 'dark';
    }

    public static function icon(?string $key): string
    {
        return self::HEADS[$key][3] ?? 'bi-wallet2';
    }

    /**
     * The list a <select> renders from.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(?string $locale = null): array
    {
        return array_map(
            fn ($key) => ['value' => $key, 'label' => self::label($key, $locale)],
            self::keys()
        );
    }

    /** Every head at zero, so a report always shows the same columns. */
    public static function emptyTotals(): array
    {
        return array_fill_keys(self::keys(), 0.0);
    }
}
