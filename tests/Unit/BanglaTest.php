<?php

namespace Tests\Unit;

use App\Support\Bangla;
use PHPUnit\Framework\TestCase;

class BanglaTest extends TestCase
{
    public function test_digits_become_bengali_and_nothing_else_changes(): void
    {
        $this->assertSame('১,২৫০টি', Bangla::digits('1,250টি'));
        $this->assertSame('২০২৬', Bangla::digits(2026));
    }

    public function test_bengali_digits_come_back_as_latin(): void
    {
        $this->assertSame('01712-345678', Bangla::latinDigits('০১৭১২-৩৪৫৬৭৮'));
        $this->assertSame('', Bangla::latinDigits(null));
    }

    public function test_money_is_printed_the_way_the_page_reads(): void
    {
        $this->assertSame('৳১,০০০', Bangla::money(1000));
        $this->assertSame('৳৫৫০', Bangla::money('550.00'));
        $this->assertSame('৳০', Bangla::money(null));
    }
}
