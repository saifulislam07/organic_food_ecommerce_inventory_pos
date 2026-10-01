<?php

namespace App\Support;

/**
 * Bengali numerals, both ways.
 *
 * The campaign pages are read in Bengali, so a price printed as ৳1,000 next to
 * copy the admin typed as ১০০০ looks like two different shops. Going the other
 * way matters more: a Bangla keyboard types ০১৭১২৩৪৫৬৭৮, and a phone check that
 * only knows 0-9 throws the whole number away.
 */
final class Bangla
{
    private const LATIN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const BENGALI = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    /** 1,250 → ১,২৫০. Anything that is not a digit is left alone. */
    public static function digits(string|int|float $value): string
    {
        return str_replace(self::LATIN, self::BENGALI, (string) $value);
    }

    /** ০১৭১২ → 01712, for anything that has to be parsed or validated. */
    public static function latinDigits(?string $value): string
    {
        return str_replace(self::BENGALI, self::LATIN, (string) $value);
    }

    /** A taka amount the way the page prints it: ৳১,২৫০. */
    public static function money(float|int|string|null $amount): string
    {
        return '৳'.self::digits(number_format((float) $amount));
    }
}
