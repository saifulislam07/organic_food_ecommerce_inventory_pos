<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * The measurement units this shop actually sells in.
 *
 *   php artisan db:seed --class=UnitSeeder
 */
class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'name_bn' => 'কেজি', 'short_code' => 'kg', 'sort_order' => 1],
            ['name' => 'Gram', 'name_bn' => 'গ্রাম', 'short_code' => 'g', 'sort_order' => 2],
            ['name' => 'Litre', 'name_bn' => 'লিটার', 'short_code' => 'L', 'sort_order' => 3],
            ['name' => 'Millilitre', 'name_bn' => 'মিলিলিটার', 'short_code' => 'ml', 'sort_order' => 4],
            ['name' => 'Piece', 'name_bn' => 'পিস', 'short_code' => 'pc', 'sort_order' => 5],
            ['name' => 'Dozen', 'name_bn' => 'ডজন', 'short_code' => 'dz', 'sort_order' => 6],
            ['name' => 'Packet', 'name_bn' => 'প্যাকেট', 'short_code' => 'pkt', 'sort_order' => 7],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['short_code' => $unit['short_code']], $unit);
        }

        $this->command?->info(count($units).' units seeded.');
    }
}
