<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;

/**
 * When a sold-out thing may still be bought, and on what terms.
 *
 * Two rules, kept here rather than spread over the card, the product page, the
 * cart and the checkout — all four have to agree, or the shop offers a button
 * that checkout then refuses.
 *
 *   1. The admin marks the product. Running out of stock alone is never enough;
 *      nothing becomes pre-orderable on its own.
 *   2. The terms fall back: the product's own note, then the shop-wide one from
 *      Settings. A shop that words it the same way everywhere types it once.
 */
class Preorder
{
    /** Settings key holding the shop-wide note, in value_en / value_bn. */
    public const SETTING_KEY = 'preorder_note';

    /** Can this exact variant be pre-ordered right now? */
    public static function allows(?Product $product, ?ProductVariant $variant): bool
    {
        if (! $product || ! $variant || ! $product->is_preorder) {
            return false;
        }

        // Only what has actually run out. A variant still on the shelf is an
        // ordinary sale, whatever the flag says.
        return $variant->available_stock <= 0;
    }

    /**
     * The terms to show for a product, in the language being read.
     *
     * Falls through the product's own note, then the shop-wide one, taking the
     * other language rather than showing nothing when only one is filled in.
     */
    public static function note(?Product $product = null): ?string
    {
        $locale = app()->getLocale() === 'bn' ? 'bn' : 'en';
        $other = $locale === 'bn' ? 'en' : 'bn';

        $candidates = [
            $product?->getAttribute("preorder_note_{$locale}"),
            $product?->getAttribute("preorder_note_{$other}"),
            Setting::value(self::SETTING_KEY, $locale),
            Setting::value(self::SETTING_KEY, $other),
        ];

        foreach ($candidates as $note) {
            if (filled($note)) {
                return trim($note);
            }
        }

        return null;
    }

    /**
     * The dialog's wording, in the language being read.
     *
     * Shared so the product card and the product page ask the same question —
     * two dialogs worded differently for the same commitment reads as a bug.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $bn = app()->getLocale() === 'bn';

        return [
            'title' => $bn ? 'প্রি-অর্ডারের শর্ত' : 'Pre-order conditions',
            'intro' => $bn
                ? 'এই পণ্যটি এখন স্টকে নেই। অর্ডার করার আগে শর্তগুলো পড়ুন।'
                : 'This item is out of stock. Please read before ordering.',
            'accept' => $bn ? 'আমি শর্তগুলো পড়েছি ও রাজি আছি' : 'I have read and accept these conditions',
            'cancel' => $bn ? 'বাতিল' : 'Cancel',
            'confirm' => $bn ? 'প্রি-অর্ডার নিশ্চিত করুন' : 'Confirm pre-order',
            'preorder' => $bn ? 'প্রি-অর্ডার করুন' : 'Pre-order',
            'preorderShort' => $bn ? 'প্রি-অর্ডার' : 'Pre-order',
        ];
    }

    /**
     * Whether pre-ordering is usable at all.
     *
     * With no terms written anywhere there is nothing for the shopper to agree
     * to, so the button stays off rather than opening an empty dialog.
     */
    public static function configured(?Product $product = null): bool
    {
        return self::note($product) !== null;
    }
}
