<?php

namespace Tests;

use App\Models\Setting;
use App\Models\SiteBlock;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Both of these are held in a static for the life of the process, which
        // in a test run is every test. Start each one with an empty slate.
        Setting::flush();
        SiteBlock::flush();

        // Order emails go out after the response; run them inline so a test
        // can see what was sent.
        $this->withoutDefer();
    }
}
