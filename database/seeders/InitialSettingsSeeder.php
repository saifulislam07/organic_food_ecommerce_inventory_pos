<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class InitialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Settings
        $settings = [
            ['key' => 'site_title', 'value_en' => 'BaburhashiBD', 'value_bn' => 'BaburhashiBD', 'type' => 'text'],
            ['key' => 'phone', 'value_en' => '+880 1XXX-XXXXXX', 'value_bn' => '+880 1XXX-XXXXXX', 'type' => 'text'],
            ['key' => 'whatsapp', 'value_en' => '+880 1XXX-XXXXXX', 'value_bn' => '+880 1XXX-XXXXXX', 'type' => 'text'],
            ['key' => 'address', 'value_en' => 'Dhaka, Bangladesh', 'value_bn' => 'ঢাকা, বাংলাদেশ', 'type' => 'textarea'],
            ['key' => 'facebook', 'value_en' => '', 'value_bn' => '', 'type' => 'text'],
            ['key' => 'hero_title', 'value_en' => 'Everything Your <span>Little One</span> Needs', 'value_bn' => 'আপনার <span>ছোট্ট সোনামণির</span><br>সব প্রয়োজন এক জায়গায়', 'type' => 'text'],
            ['key' => 'hero_desc', 'value_en' => 'Baby essentials, kids fashion and toys, delivered across Bangladesh.', 'value_bn' => 'শিশুদের পোশাক, খেলনা ও নিত্যপ্রয়োজনীয় সবকিছু — সারাদেশে ডেলিভারি।', 'type' => 'textarea'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // Categories
        $categories = [
            [
                'name_en' => 'Baby Clothing',
                'name_bn' => 'নবজাতকের পোশাক',
                'slug' => 'baby-clothing',
                'description_en' => 'Soft cotton clothing for newborns and infants.',
                'description_bn' => 'নবজাতক ও ছোট্ট সোনামণিদের জন্য নরম সুতির পোশাক।',
                'is_active' => true,
            ],
            [
                'name_en' => 'Toys & Games',
                'name_bn' => 'খেলনা',
                'slug' => 'toys-games',
                'description_en' => 'Educational and safe toys for every age.',
                'description_bn' => 'সব বয়সের জন্য শিক্ষামূলক ও নিরাপদ খেলনা।',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Essential Pages
        $pages = [
            [
                'slug' => 'about-us',
                'title_en' => 'About Us',
                'title_bn' => 'আমাদের সম্পর্কে',
                'content_en' => 'Welcome to BaburhashiBD. We bring safe, quality baby and kids essentials to your doorstep.',
                'content_bn' => 'BaburhashiBD-তে আপনাকে স্বাগতম। আমরা আপনার শিশুর জন্য নিরাপদ ও মানসম্পন্ন পণ্য পৌঁছে দিই।',
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
