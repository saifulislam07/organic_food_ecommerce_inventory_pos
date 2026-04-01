<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Setting;
use App\Models\Page;

class InitialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Settings
        $settings = [
            ['key' => 'site_title', 'value_en' => 'Mango Hut', 'value_bn' => 'ম্যাঙ্গো হাট', 'type' => 'text'],
            ['key' => 'phone', 'value_en' => '01716-952365', 'value_bn' => '01716-952365', 'type' => 'text'],
            ['key' => 'whatsapp', 'value_en' => '01716-952365', 'value_bn' => '01716-952365', 'type' => 'text'],
            ['key' => 'address', 'value_en' => 'Chapainawabganj, Rajshahi, Bangladesh', 'value_bn' => 'চাঁপাই নবাবগঞ্জ, রাজশাহী, বাংলাদেশ', 'type' => 'textarea'],
            ['key' => 'facebook', 'value_en' => 'https://facebook.com/mangohut', 'value_bn' => 'https://facebook.com/mangohut', 'type' => 'text'],
            ['key' => 'hero_title', 'value_en' => 'Pure & Organic <br><span>Nature</span> Online Market', 'value_bn' => 'খাঁটি ও <span>অর্গানিক</span><br>পণ্যের অনলাইন বাজার', 'type' => 'text'],
            ['key' => 'hero_desc', 'value_en' => 'Directly from Chapainawabganj to your doorstep. All natural and seasonal products.', 'value_bn' => 'সরাসরি চাঁপাই নবাবগঞ্জ থেকে আপনার দোরগোড়ায়। সকল প্রাকৃতিক ও মৌসুমী পণ্য।', 'type' => 'textarea'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // Categories
        $categories = [
            [
                'name_en' => 'Premium Mangoes',
                'name_bn' => 'প্রিমিয়াম আম',
                'slug' => 'mangoes',
                'description_en' => 'The finest mangoes from Chapainawabganj.',
                'description_bn' => 'চাঁপাই নবাবগঞ্জের সেরা আমসমূহ।',
                'is_active' => true,
            ],
            [
                'name_en' => 'Organic Honey',
                'name_bn' => 'খাঁটি মধু',
                'slug' => 'honey',
                'description_en' => '100% natural flower honey.',
                'description_bn' => '১০০% প্রাকৃতিক ফুলের মধু।',
                'is_active' => true,
            ]
        ];

        foreach ($categories as $cat) {
            \App\Models\Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Essential Pages
        $pages = [
            [
                'slug' => 'about-us',
                'title_en' => 'About Us',
                'title_bn' => 'আমাদের সম্পর্কে',
                'content_en' => 'Welcome to Mango Hut. We provide the best organic products from Chapainawabganj.',
                'content_bn' => 'ম্যাঙ্গো হাটে আপনাকে স্বাগতম। আমরা সরাসরি চাঁপাই নবাবগঞ্জ থেকে সেরা অর্গানিক পণ্য সরবরাহ করি।',
            ],
            [
                'slug' => 'terms-and-conditions',
                'title_en' => 'Terms & Conditions',
                'title_bn' => 'টার্মস ও কন্ডিশনস',
                'content_en' => 'By using our website, you agree to our terms...',
                'content_bn' => 'আমাদের ওয়েবসাইট ব্যবহার করে আপনি আমাদের শর্তাবলীতে সম্মত হচ্ছেন...',
            ],
            [
                'slug' => 'privacy-policy',
                'title_en' => 'Privacy Policy',
                'title_bn' => 'প্রাইভেসি পলিসি',
                'content_en' => 'Your privacy is important to us...',
                'content_bn' => 'আপনার গোপনীয়তা আমাদের কাছে গুরুত্বপূর্ণ...',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
