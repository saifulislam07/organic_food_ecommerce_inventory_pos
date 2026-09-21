<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The seeded "Free Delivery" service card spelled the amount out — "৳2,000" —
 * while the figure it quotes lives in Settings > free_delivery_threshold.
 * Raising the threshold there left the front page still promising the old one.
 *
 * The card already substitutes :threshold when it renders, so the fix is to
 * put the placeholder where the number was. Only rows that still carry the
 * exact seeded wording are touched; anything the admin has rewritten is left
 * alone, because their number may be deliberate.
 */
return new class extends Migration
{
    /** column => [as seeded, with the placeholder] */
    private const REPHRASED = [
        'subtitle_en' => ['On orders over ৳2,000', 'On orders over ৳:threshold'],
        'subtitle_bn' => ['৳২,০০০+ অর্ডারে', '৳:threshold+ অর্ডারে'],
    ];

    public function up(): void
    {
        $this->swap(0, 1);
    }

    public function down(): void
    {
        $this->swap(1, 0);
    }

    private function swap(int $from, int $to): void
    {
        foreach (self::REPHRASED as $column => $wording) {
            DB::table('site_blocks')
                ->where('group', 'service')
                ->where($column, $wording[$from])
                ->update([$column => $wording[$to], 'updated_at' => now()]);
        }
    }
};
