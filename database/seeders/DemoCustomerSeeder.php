<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo customers with addresses and a spread of order history, for trying out
 * the admin Customers screens. Not called from DatabaseSeeder, so it never runs
 * on a real install by accident:
 *
 *   php artisan db:seed --class=DemoCustomerSeeder
 *
 * Every demo customer signs in with password "123456". Safe to re-run: a
 * customer who already exists keeps their orders and gets no new ones.
 *
 * Orders are history only — they do not deduct stock.
 */
class DemoCustomerSeeder extends Seeder
{
    private const PASSWORD = '123456';

    /**
     * name, mobile, email, area, address, joined (days ago), orders, blocked
     *
     * orders: [days ago, status, how many line items]
     */
    private const CUSTOMERS = [
        ['Farzana Akter', '01711000001', 'farzana@example.com', 'dhaka_inside', 'House 12, Road 5, Dhanmondi, Dhaka', 210, [
            [180, 'delivered', 2], [120, 'delivered', 3], [60, 'delivered', 1], [12, 'delivered', 2], [2, 'processing', 1],
        ], false],
        ['Tanvir Ahmed', '01811000002', 'tanvir.ahmed@example.com', 'dhaka_inside', 'Flat 4B, Block C, Mirpur 10, Dhaka', 150, [
            [140, 'delivered', 1], [75, 'cancelled', 2], [20, 'delivered', 2],
        ], false],
        ['Sadia Islam', '01911000003', null, 'dhaka_outside', 'Agrabad C/A, Chattogram', 95, [
            [90, 'delivered', 3], [30, 'shipped', 1],
        ], false],
        ['Rakib Hasan', '01611000004', 'rakib.hasan@example.com', 'dhaka_outside', 'Zindabazar, Sylhet', 60, [
            [55, 'delivered', 1],
        ], false],
        ['Nusrat Jahan', '01511000005', 'nusrat.j@example.com', 'dhaka_inside', 'Sector 7, Uttara, Dhaka', 45, [
            [40, 'delivered', 2], [10, 'confirmed', 2], [1, 'pending', 1],
        ], false],
        ['Mahmudul Karim', '01311000006', null, 'dhaka_outside', 'Shaheb Bazar, Rajshahi', 30, [
            [25, 'cancelled', 1],
        ], false],
        ['Shirin Sultana', '01711000007', 'shirin.s@example.com', 'dhaka_inside', 'Road 27, Banani, Dhaka', 20, [], false],
        ['Arif Hossain', '01811000008', 'arif.h@example.com', 'dhaka_outside', 'KDA Avenue, Khulna', 14, [
            [8, 'delivered', 2],
        ], false],
        // Lots of cancelled cash-on-delivery orders: the usual reason to block.
        ['Kamrul Islam', '01911000009', null, 'dhaka_inside', 'Jatrabari, Dhaka', 80, [
            [70, 'cancelled', 2], [50, 'cancelled', 1], [35, 'cancelled', 3],
        ], true],
        ['Jannatul Ferdous', '01611000010', 'jannat.f@example.com', 'dhaka_inside', 'Bashundhara R/A, Dhaka', 5, [
            [3, 'pending', 2],
        ], false],
        ['Imran Chowdhury', '01511000011', 'imran.c@example.com', 'dhaka_outside', 'Station Road, Cumilla', 3, [], false],
        ['Rumana Begum', '01311000012', null, 'dhaka_outside', 'Sadar Road, Barishal', 1, [], false],
    ];

    /** Orders placed without signing in on a demo customer's number, to show the "Guest Orders" panel. */
    private const GUEST_ORDERS = [
        ['01711000001', 250, 'delivered', 1],
        ['01911000003', 15, 'delivered', 2],
    ];

    public function run(): void
    {
        $variants = ProductVariant::with('product')->get();

        if ($variants->isEmpty()) {
            $this->command?->warn('No products yet — seeding customers without orders. Run DatabaseSeeder first for order history.');
        }

        $created = 0;

        DB::transaction(function () use ($variants, &$created) {
            foreach (self::CUSTOMERS as [$name, $mobile, $email, $area, $address, $joinedDaysAgo, $orders, $blocked]) {
                $customer = User::firstWhere('mobile', $mobile);

                if ($customer) {
                    continue;
                }

                $joined = now()->subDays($joinedDaysAgo)->setTime(rand(9, 21), rand(0, 59));

                $customer = User::create([
                    'name' => $name,
                    'email' => $email,
                    'mobile' => $mobile,
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'customer',
                ]);

                $customer->forceFill([
                    'created_at' => $joined,
                    'updated_at' => $joined,
                    'blocked_at' => $blocked ? now()->subDays(20) : null,
                ])->save();

                UserAddress::create([
                    'user_id' => $customer->id,
                    'name' => $name,
                    'phone' => $mobile,
                    'area' => $area,
                    'address' => $address,
                    'is_default' => true,
                ]);

                foreach ($orders as [$daysAgo, $status, $lines]) {
                    $this->order($variants, $name, $mobile, $email, $area, $address, $daysAgo, $status, $lines, $customer->id);
                }

                $created++;
            }

            foreach (self::GUEST_ORDERS as [$mobile, $daysAgo, $status, $lines]) {
                [$name, , $email, $area, $address] = collect(self::CUSTOMERS)->firstWhere(1, $mobile);

                $exists = Order::whereNull('user_id')->where('customer_phone', $mobile)->exists();

                if (! $exists) {
                    $this->order($variants, $name, $mobile, $email, $area, $address, $daysAgo, $status, $lines, null);
                }
            }
        });

        $this->command?->info("{$created} demo customer(s) created. Sign in with any of their mobiles and password ".self::PASSWORD.'.');
    }

    private function order($variants, string $name, string $mobile, ?string $email, string $area, string $address, int $daysAgo, string $status, int $lines, ?int $userId): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        $placed = now()->subDays($daysAgo)->setTime(rand(9, 22), rand(0, 59));
        $items = $variants->random(min($lines, $variants->count()));

        $rows = $items->map(function (ProductVariant $variant) {
            $quantity = rand(1, 3);
            $price = (float) ($variant->sale_price ?: $variant->price);

            return [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total' => $price * $quantity,
            ];
        });

        $subtotal = $rows->sum('total');
        $delivery = $area === 'dhaka_inside' ? 60 : 120;

        $order = Order::create([
            'user_id' => $userId,
            'order_number' => 'MH-'.$placed->format('Ymd').'-'.strtoupper(Str::random(5)),
            'customer_name' => $name,
            'customer_phone' => $mobile,
            'customer_email' => $email,
            'customer_address' => $address,
            'customer_area' => $area,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'delivery_charge' => $delivery,
            'total' => $subtotal + $delivery,
            'status' => $status,
            'payment_method' => 'cod',
            'source' => 'website',
            'delivered_at' => $status === 'delivered' ? $placed->copy()->addDays(rand(1, 3)) : null,
        ]);

        $order->items()->createMany($rows->all());

        $this->stamp($order, $placed);
    }

    /** Backdate the order and its lines so the history reads as real history. */
    private function stamp(Order $order, Carbon $at): void
    {
        $order->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        $order->items()->update(['created_at' => $at, 'updated_at' => $at]);
    }
}
