<?php

namespace App\Support;

/**
 * A money figure spelled out, because an invoice is a document people argue
 * over and a figure in words is what settles it — a digit can be altered by
 * hand, a sentence cannot.
 *
 * Counted the way this market counts: crore and lakh, not million. Ten million
 * taka reads "এক কোটি" here, and an invoice that said "ten million" would be
 * read twice by everyone who picked it up.
 *
 * Both languages are spelled out rather than transliterated, so the Bangla
 * invoice reads as Bangla rather than as English written in Bangla letters.
 */
class AmountInWords
{
    /** 0–19 spelled out; everything larger is built from these plus TENS_EN. */
    private const ONES_EN = [
        'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS_EN = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    /**
     * 0–99 in Bangla, written out in full.
     *
     * Bangla does not build its two-digit numbers from a tens word and a ones
     * word the way English does — ঊনচল্লিশ is not "thirty" and "nine" put
     * together — so the only correct way to do this is the whole table.
     */
    private const ONES_BN = [
        'শূন্য', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়',
        'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোল', 'সতেরো', 'আঠারো', 'ঊনিশ',
        'বিশ', 'একুশ', 'বাইশ', 'তেইশ', 'চব্বিশ', 'পঁচিশ', 'ছাব্বিশ', 'সাতাশ', 'আটাশ', 'ঊনত্রিশ',
        'ত্রিশ', 'একত্রিশ', 'বত্রিশ', 'তেত্রিশ', 'চৌত্রিশ', 'পঁয়ত্রিশ', 'ছত্রিশ', 'সাঁইত্রিশ', 'আটত্রিশ', 'ঊনচল্লিশ',
        'চল্লিশ', 'একচল্লিশ', 'বিয়াল্লিশ', 'তেতাল্লিশ', 'চুয়াল্লিশ', 'পঁয়তাল্লিশ', 'ছেচল্লিশ', 'সাতচল্লিশ', 'আটচল্লিশ', 'ঊনপঞ্চাশ',
        'পঞ্চাশ', 'একান্ন', 'বায়ান্ন', 'তিপ্পান্ন', 'চুয়ান্ন', 'পঞ্চান্ন', 'ছাপ্পান্ন', 'সাতান্ন', 'আটান্ন', 'ঊনষাট',
        'ষাট', 'একষট্টি', 'বাষট্টি', 'তেষট্টি', 'চৌষট্টি', 'পঁয়ষট্টি', 'ছেষট্টি', 'সাতষট্টি', 'আটষট্টি', 'ঊনসত্তর',
        'সত্তর', 'একাত্তর', 'বাহাত্তর', 'তিয়াত্তর', 'চুয়াত্তর', 'পঁচাত্তর', 'ছিয়াত্তর', 'সাতাত্তর', 'আটাত্তর', 'ঊনআশি',
        'আশি', 'একাশি', 'বিরাশি', 'তিরাশি', 'চুরাশি', 'পঁচাশি', 'ছিয়াশি', 'সাতাশি', 'আটাশি', 'ঊননব্বই',
        'নব্বই', 'একানব্বই', 'বিরানব্বই', 'তিরানব্বই', 'চুরানব্বই', 'পঁচানব্বই', 'ছিয়ানব্বই', 'সাতানব্বই', 'আটানব্বই', 'নিরানব্বই',
    ];

    /**
     * The place names, largest first, with the value of each place.
     *
     * Ordered this way so one loop serves both languages: divide by the place,
     * name the quotient, carry the remainder down to the next.
     *
     * @var array<int, array{int, string, string}> [divisor, English, বাংলা]
     */
    private const PLACES = [
        [10000000, 'Crore', 'কোটি'],
        [100000, 'Lakh', 'লক্ষ'],
        [1000, 'Thousand', 'হাজার'],
        [100, 'Hundred', 'শত'],
    ];

    /**
     * A taka figure as a finished sentence, ready to print on an invoice.
     *
     * @param  float|int|string|null  $amount
     */
    public static function taka($amount, ?string $locale = null): string
    {
        $locale = self::locale($locale);

        $value = round((float) $amount, 2);
        $negative = $value < 0;
        $value = abs($value);

        $whole = (int) floor($value);
        $poisha = (int) round(($value - $whole) * 100);

        // Rounding the fraction can land on a full hundred (49.999 -> 50.00),
        // which would otherwise print "Forty-Nine Taka and One Hundred Poisha".
        if ($poisha === 100) {
            $whole++;
            $poisha = 0;
        }

        $words = $locale === 'bn'
            ? self::sentenceBn($whole, $poisha)
            : self::sentenceEn($whole, $poisha);

        // A refund line can go negative; say so rather than printing the
        // absolute value and losing the sign.
        return $negative
            ? ($locale === 'bn' ? 'ঋণাত্মক ' : 'Minus ').$words
            : $words;
    }

    /** Just the number — no currency, no "only" — for anything that is not money. */
    public static function number(int $number, ?string $locale = null): string
    {
        $locale = self::locale($locale);

        $words = $locale === 'bn' ? self::convertBn(abs($number)) : self::convertEn(abs($number));

        if ($number >= 0) {
            return $words;
        }

        return ($locale === 'bn' ? 'ঋণাত্মক ' : 'Minus ').$words;
    }

    /** Anything that is not an explicit 'bn' falls back to English. */
    private static function locale(?string $locale): string
    {
        return ($locale ?: app()->getLocale()) === 'bn' ? 'bn' : 'en';
    }

    private static function sentenceEn(int $whole, int $poisha): string
    {
        $sentence = self::convertEn($whole).' Taka';

        if ($poisha > 0) {
            $sentence .= ' and '.self::convertEn($poisha).' Poisha';
        }

        return $sentence.' Only';
    }

    private static function sentenceBn(int $whole, int $poisha): string
    {
        $sentence = self::convertBn($whole).' টাকা';

        if ($poisha > 0) {
            $sentence .= ' '.self::convertBn($poisha).' পয়সা';
        }

        return $sentence.' মাত্র';
    }

    private static function convertEn(int $number): string
    {
        if ($number < 100) {
            return self::underHundredEn($number);
        }

        $parts = [];

        foreach (self::PLACES as [$divisor, $name]) {
            $count = intdiv($number, $divisor);

            if ($count === 0) {
                continue;
            }

            // The count for a place can itself be three digits — 1,25,00,000 is
            // "One Crore Twenty-Five Lakh" — so the quotient recurses.
            $parts[] = self::convertEn($count).' '.$name;
            $number %= $divisor;
        }

        if ($number > 0) {
            $parts[] = self::underHundredEn($number);
        }

        return implode(' ', $parts);
    }

    private static function underHundredEn(int $number): string
    {
        if ($number < 20) {
            return self::ONES_EN[$number];
        }

        $tens = self::TENS_EN[intdiv($number, 10)];
        $ones = $number % 10;

        return $ones === 0 ? $tens : $tens.'-'.self::ONES_EN[$ones];
    }

    private static function convertBn(int $number): string
    {
        if ($number < 100) {
            return self::ONES_BN[$number];
        }

        $parts = [];

        foreach (self::PLACES as [$divisor, , $name]) {
            $count = intdiv($number, $divisor);

            if ($count === 0) {
                continue;
            }

            // শত joins the digit with no space — পাঁচশত, not পাঁচ শত — while the
            // larger places stand as words of their own.
            $parts[] = $divisor === 100
                ? self::convertBn($count).$name
                : self::convertBn($count).' '.$name;

            $number %= $divisor;
        }

        if ($number > 0) {
            $parts[] = self::ONES_BN[$number];
        }

        return implode(' ', $parts);
    }
}
