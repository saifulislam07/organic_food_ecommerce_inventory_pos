<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_blocks', function (Blueprint $table) {
            $table->id();
            // One of App\Models\SiteBlock::GROUPS — which list this row belongs to.
            $table->string('group', 32);
            $table->string('title_en');
            $table->string('title_bn')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('subtitle_bn')->nullable();
            $table->string('icon', 64)->nullable();      // a Bootstrap Icons name, without the bi- prefix
            $table->string('image')->nullable();         // promo tiles only
            $table->string('url')->nullable();           // a path, a full URL, or blank for the home page
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Every render asks for one group's active rows in order.
            $table->index(['group', 'is_active', 'sort_order']);
        });

        // The storefront carried this content hard-coded until now. Seeding it
        // here means an existing shop reads exactly the same after migrating,
        // and starts editing from what it already had rather than from nothing.
        DB::table('site_blocks')->insert($this->defaults());
    }

    public function down(): void
    {
        Schema::dropIfExists('site_blocks');
    }

    private function defaults(): array
    {
        $now = now();
        $rows = [];
        $order = [];

        $add = function (string $group, array $row) use (&$rows, &$order, $now) {
            $order[$group] = ($order[$group] ?? -1) + 1;

            $rows[] = array_merge([
                'group' => $group,
                'title_bn' => null,
                'subtitle_en' => null,
                'subtitle_bn' => null,
                'icon' => null,
                'image' => null,
                'url' => null,
                'is_highlighted' => false,
                'is_active' => true,
                'sort_order' => $order[$group],
                'created_at' => $now,
                'updated_at' => $now,
            ], $row);
        };

        $add('header_menu', ['title_en' => 'Home', 'title_bn' => 'হোম', 'url' => '/', 'icon' => 'house-door']);
        $add('header_menu', ['title_en' => 'Shop', 'title_bn' => 'শপ', 'url' => '/shop', 'icon' => 'shop']);
        $add('header_menu', [
            'title_en' => 'New Arrivals', 'title_bn' => 'নতুন পণ্য',
            'url' => '/shop?sort=latest', 'icon' => 'lightning-charge-fill', 'is_highlighted' => true,
        ]);
        $add('header_menu', ['title_en' => 'About Us', 'title_bn' => 'আমাদের সম্পর্কে', 'url' => '/about-us']);
        $add('header_menu', ['title_en' => 'Contact', 'title_bn' => 'যোগাযোগ', 'url' => '/contact']);

        $add('service', [
            'title_en' => 'Free Delivery', 'title_bn' => 'ফ্রি ডেলিভারি',
            'subtitle_en' => 'On orders over ৳2,000', 'subtitle_bn' => '৳২,০০০+ অর্ডারে',
            'icon' => 'truck',
        ]);
        $add('service', [
            'title_en' => '100% Authentic', 'title_bn' => '১০০% খাঁটি',
            'subtitle_en' => 'Straight from the source', 'subtitle_bn' => 'সরাসরি উৎস থেকে',
            'icon' => 'patch-check',
        ]);
        $add('service', [
            'title_en' => 'Cash on Delivery', 'title_bn' => 'ক্যাশ অন ডেলিভারি',
            'subtitle_en' => 'Pay when it arrives', 'subtitle_bn' => 'হাতে পেয়ে টাকা দিন',
            'icon' => 'cash-coin',
        ]);
        $add('service', [
            'title_en' => 'Easy Support', 'title_bn' => 'সহজ সাপোর্ট',
            'subtitle_en' => 'We are a call away', 'subtitle_bn' => 'এক কলেই পাশে আছি',
            'icon' => 'headset',
        ]);

        $add('promo', [
            'title_en' => 'Free delivery on every large order',
            'title_bn' => 'বড় অর্ডারে ফ্রি ডেলিভারি',
            'subtitle_en' => 'Free Delivery', 'subtitle_bn' => 'ফ্রি ডেলিভারি',
            'url' => '/shop',
        ]);
        $add('promo', [
            'title_en' => 'Lowest priced items first',
            'title_bn' => 'কম দামের পণ্য আগে দেখুন',
            'subtitle_en' => 'Best Value', 'subtitle_bn' => 'সাশ্রয়ী দাম',
            'url' => '/shop?sort=price_low',
        ]);
        $add('promo', [
            'title_en' => 'Order over WhatsApp',
            'title_bn' => 'WhatsApp এ অর্ডার করুন',
            'subtitle_en' => 'Easy Order', 'subtitle_bn' => 'সহজ অর্ডার',
            'url' => '/contact',
        ]);

        $add('payment', ['title_en' => 'Cash on Delivery', 'title_bn' => 'ক্যাশ অন ডেলিভারি', 'icon' => 'cash-coin']);
        $add('payment', ['title_en' => 'bKash', 'icon' => 'phone']);
        $add('payment', ['title_en' => 'Nagad', 'icon' => 'phone']);
        $add('payment', ['title_en' => 'Bank Transfer', 'title_bn' => 'ব্যাংক ট্রান্সফার', 'icon' => 'bank']);

        $add('footer_menu', ['title_en' => 'Dashboard', 'title_bn' => 'ড্যাশবোর্ড', 'url' => '/customer/dashboard']);
        $add('footer_menu', ['title_en' => 'Cart', 'title_bn' => 'কার্ট', 'url' => '/cart']);
        $add('footer_menu', ['title_en' => 'Shipping Policy', 'title_bn' => 'শিপিং পলিসি', 'url' => '/shipping-policy']);
        $add('footer_menu', ['title_en' => 'Return Policy', 'title_bn' => 'রিটার্ন পলিসি', 'url' => '/return-policy']);

        $add('footer_bottom', ['title_en' => 'Terms & Conditions', 'title_bn' => 'টার্মস ও কন্ডিশন', 'url' => '/terms-and-conditions']);
        $add('footer_bottom', ['title_en' => 'Privacy Policy', 'title_bn' => 'প্রাইভেসি পলিসি', 'url' => '/privacy-policy']);
        $add('footer_bottom', ['title_en' => 'Contact', 'title_bn' => 'যোগাযোগ', 'url' => '/contact']);

        return $rows;
    }
};
