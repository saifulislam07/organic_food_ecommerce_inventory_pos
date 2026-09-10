<?php

namespace Tests\Unit;

use App\Support\AmountInWords;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The invoice total, spelled out.
 *
 * The cases worth guarding are the ones a naive implementation gets wrong: the
 * Indian places (a lakh is not a hundred thousand read out loud), the Bangla
 * two-digit words that are not built from parts, and the rounding boundary
 * where a fraction becomes a whole taka.
 */
class AmountInWordsTest extends TestCase
{
    public static function english(): array
    {
        return [
            'zero' => [0, 'Zero Taka Only'],
            'single digit' => [7, 'Seven Taka Only'],
            'teen' => [15, 'Fifteen Taka Only'],
            'hyphenated tens' => [35, 'Thirty-Five Taka Only'],
            'round hundred' => [100, 'One Hundred Taka Only'],
            'hundred and change' => [105, 'One Hundred Five Taka Only'],
            'thousands' => [2500, 'Two Thousand Five Hundred Taka Only'],
            'lakh, not hundred thousand' => [100000, 'One Lakh Taka Only'],
            'a million is ten lakh' => [1000000, 'Ten Lakh Taka Only'],
            'crore' => [10000000, 'One Crore Taka Only'],
            'crore with a lakh on top' => [12500000, 'One Crore Twenty-Five Lakh Taka Only'],
            'everything at once' => [
                1234567,
                'Twelve Lakh Thirty-Four Thousand Five Hundred Sixty-Seven Taka Only',
            ],
        ];
    }

    #[DataProvider('english')]
    public function test_it_spells_taka_in_english($amount, string $expected): void
    {
        $this->assertSame($expected, AmountInWords::taka($amount, 'en'));
    }

    public static function bangla(): array
    {
        return [
            'zero' => [0, 'শূন্য টাকা মাত্র'],
            'irregular two digit' => [39, 'ঊনচল্লিশ টাকা মাত্র'],
            'another one' => [35, 'পঁয়ত্রিশ টাকা মাত্র'],
            'hundred joins the digit' => [500, 'পাঁচশত টাকা মাত্র'],
            'thousands' => [2500, 'দুই হাজার পাঁচশত টাকা মাত্র'],
            'lakh' => [100000, 'এক লক্ষ টাকা মাত্র'],
            'crore' => [10000000, 'এক কোটি টাকা মাত্র'],
        ];
    }

    #[DataProvider('bangla')]
    public function test_it_spells_taka_in_bangla($amount, string $expected): void
    {
        $this->assertSame($expected, AmountInWords::taka($amount, 'bn'));
    }

    public function test_poisha_are_spelled_out_separately(): void
    {
        $this->assertSame('One Hundred Taka and Fifty Poisha Only', AmountInWords::taka(100.50, 'en'));
        $this->assertSame('একশত টাকা পঞ্চাশ পয়সা মাত্র', AmountInWords::taka(100.50, 'bn'));
    }

    public function test_a_whole_amount_says_nothing_about_poisha(): void
    {
        $this->assertStringNotContainsString('Poisha', AmountInWords::taka(100.00, 'en'));
        $this->assertStringNotContainsString('পয়সা', AmountInWords::taka(100.00, 'bn'));
    }

    /** A fraction that rounds to a full hundred must carry, not print "One Hundred Poisha". */
    public function test_a_fraction_that_rounds_up_becomes_a_whole_taka(): void
    {
        $this->assertSame('Fifty Taka Only', AmountInWords::taka(49.999, 'en'));
    }

    public function test_it_rounds_to_the_nearest_poisha(): void
    {
        $this->assertSame('Ten Taka and Twelve Poisha Only', AmountInWords::taka(10.124, 'en'));
    }

    public function test_a_negative_amount_says_so(): void
    {
        $this->assertSame('Minus Fifty Taka Only', AmountInWords::taka(-50, 'en'));
        $this->assertSame('ঋণাত্মক পঞ্চাশ টাকা মাত্র', AmountInWords::taka(-50, 'bn'));
    }

    public function test_it_follows_the_active_locale_when_none_is_given(): void
    {
        app()->setLocale('bn');
        $this->assertSame('একশত টাকা মাত্র', AmountInWords::taka(100));

        app()->setLocale('en');
        $this->assertSame('One Hundred Taka Only', AmountInWords::taka(100));
    }

    public function test_a_plain_number_carries_no_currency(): void
    {
        $this->assertSame('Twenty-Five', AmountInWords::number(25, 'en'));
        $this->assertSame('পঁচিশ', AmountInWords::number(25, 'bn'));
    }

    public function test_a_string_amount_is_accepted(): void
    {
        // Decimal casts hand back strings, and the invoice passes them straight in.
        $this->assertSame('Two Thousand Five Hundred Taka Only', AmountInWords::taka('2500.00', 'en'));
    }
}
