<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions first, so the admin can be granted the Super Admin role.
        $this->call(PermissionSeeder::class);

        // Single source of truth for how an administrator is created.
        $this->call(AdminSeeder::class);

        // Suppliers
        Supplier::updateOrCreate(
            ['phone' => '01700000001'],
            [
                'name' => 'Little Star Kids Wholesale',
                'contact_person' => 'Ms. Nusrat',
                'email' => 'contact@littlestar-example.com',
                'address' => 'Dhaka, Bangladesh',
            ]
        );

        // Categories
        $categories = [
            ['name' => 'নবজাতকের পোশাক (Baby Clothing)', 'slug' => 'baby-clothing', 'description' => 'নরম সুতির কাপড়ে তৈরি নবজাতক ও ছোট্ট সোনামণিদের পোশাক - Soft cotton clothing for newborns and infants', 'sort_order' => 1, 'image' => 'baby-clothing.jpg'],
            ['name' => 'কিডস ফ্যাশন (Kids Fashion)', 'slug' => 'kids-fashion', 'description' => 'ছেলে ও মেয়ে শিশুদের জন্য ট্রেন্ডি পোশাক ও জুতা - Trendy clothing and shoes for boys and girls', 'sort_order' => 2, 'image' => 'kids-fashion.jpg'],
            ['name' => 'ডায়াপার ও ওয়াইপস (Diapers & Wipes)', 'slug' => 'diapers-wipes', 'description' => 'আরামদায়ক ও লিক-প্রুফ ডায়াপার এবং সফট বেবি ওয়াইপস - Comfortable, leak-proof diapers and soft baby wipes', 'sort_order' => 3, 'image' => 'diapers-wipes.jpg'],
            ['name' => 'ফিডিং ও নার্সিং (Feeding & Nursing)', 'slug' => 'feeding-nursing', 'description' => 'ফিডার, দুধ পাম্প ও শিশুর খাবারের সরঞ্জাম - Feeding bottles, breast pumps and nursing essentials', 'sort_order' => 4, 'image' => 'feeding-nursing.jpg'],
            ['name' => 'খেলনা (Toys & Games)', 'slug' => 'toys-games', 'description' => 'শিক্ষামূলক ও নিরাপদ খেলনা, সব বয়সের জন্য - Educational and safe toys for every age', 'sort_order' => 5, 'image' => 'toys-games.jpg'],
            ['name' => 'বেবি কেয়ার (Baby Care)', 'slug' => 'baby-care', 'description' => 'বেবি লোশন, শ্যাম্পু ও যত্নের নিরাপদ পণ্য - Gentle lotions, shampoos and baby skincare', 'sort_order' => 6, 'image' => 'baby-care.jpg'],
            ['name' => 'নার্সারি ও বেডিং (Nursery & Bedding)', 'slug' => 'nursery-bedding', 'description' => 'বেবি বেড, মশারি ও নরম বিছানার সামগ্রী - Cribs, mosquito nets and soft bedding for the nursery', 'sort_order' => 7, 'image' => 'nursery-bedding.jpg'],
            ['name' => 'স্কুল ও স্টেশনারি (School & Stationery)', 'slug' => 'school-stationery', 'description' => 'স্কুল ব্যাগ, টিফিন বক্স ও স্টেশনারি সামগ্রী - School bags, lunch boxes and stationery', 'sort_order' => 8, 'image' => 'school-stationery.jpg'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Products with Variants
        $products = [
            // Baby Clothing
            [
                'category_slug' => 'baby-clothing',
                'name' => 'নবজাতক কটন রোম্পার সেট (Newborn Cotton Romper Set)',
                'slug' => 'newborn-cotton-romper-set',
                'short_description' => '০-৯ মাসের শিশুদের জন্য নরম সুতির রোম্পার সেট',
                'description' => '১০০% সুতি কাপড়ে তৈরি, ঘামাচি প্রতিরোধী ও নবজাতকের কোমল ত্বকের জন্য নিরাপদ রোম্পার সেট। প্রতি সেটে ৩টি করে রোম্পার থাকে।',
                'is_featured' => true,
                'is_bestseller' => true,
                'image' => 'baby-romper-set.jpg',
                'variants' => [
                    ['name' => '0-3 মাস (0-3 Months)', 'weight_kg' => 0.3, 'price' => 650, 'sale_price' => 550, 'stock' => 60],
                    ['name' => '3-6 মাস (3-6 Months)', 'weight_kg' => 0.35, 'price' => 700, 'sale_price' => 600, 'stock' => 50],
                    ['name' => '6-9 মাস (6-9 Months)', 'weight_kg' => 0.4, 'price' => 750, 'sale_price' => 650, 'stock' => 40],
                ],
            ],
            [
                'category_slug' => 'baby-clothing',
                'name' => 'বেবি ফ্লিস উইন্টার জ্যাকেট (Baby Fleece Winter Jacket)',
                'slug' => 'baby-fleece-winter-jacket',
                'short_description' => 'শীতে শিশুকে উষ্ণ রাখতে নরম ফ্লিস জ্যাকেট',
                'description' => 'হালকা অথচ উষ্ণ ফ্লিস কাপড়ে তৈরি হুডি জ্যাকেট। ইনডোর ও আউটডোর দুই জায়গাতেই আরামদায়ক।',
                'is_trending' => true,
                'image' => 'baby-winter-jacket.jpg',
                'variants' => [
                    ['name' => '1-2 বছর (1-2 Years)', 'weight_kg' => 0.3, 'price' => 800, 'sale_price' => 700, 'stock' => 35],
                    ['name' => '3-4 বছর (3-4 Years)', 'weight_kg' => 0.35, 'price' => 950, 'sale_price' => 850, 'stock' => 30],
                    ['name' => '5-6 বছর (5-6 Years)', 'weight_kg' => 0.4, 'price' => 1100, 'sale_price' => 1000, 'stock' => 20],
                ],
            ],
            // Kids Fashion
            [
                'category_slug' => 'kids-fashion',
                'name' => 'গার্লস পার্টি ফ্রক (Girls Party Frock)',
                'slug' => 'girls-party-frock',
                'short_description' => 'উৎসব ও অনুষ্ঠানের জন্য সুন্দর পার্টি ফ্রক',
                'description' => 'নেট ও সাটিন কাপড়ের কম্বিনেশনে তৈরি ফ্রক, আরামদায়ক লাইনিংসহ। জন্মদিন ও উৎসবের জন্য উপযুক্ত।',
                'is_featured' => true,
                'is_bestseller' => true,
                'image' => 'girls-party-frock.jpg',
                'variants' => [
                    ['name' => '2-3 বছর (2-3 Years)', 'weight_kg' => 0.25, 'price' => 900, 'sale_price' => 750, 'stock' => 25],
                    ['name' => '4-5 বছর (4-5 Years)', 'weight_kg' => 0.3, 'price' => 1000, 'sale_price' => 850, 'stock' => 20],
                    ['name' => '6-7 বছর (6-7 Years)', 'weight_kg' => 0.35, 'price' => 1100, 'sale_price' => 950, 'stock' => 15],
                ],
            ],
            [
                'category_slug' => 'kids-fashion',
                'name' => 'বয়েজ ক্যাজুয়াল শার্ট-প্যান্ট সেট (Boys Casual Shirt-Pant Set)',
                'slug' => 'boys-casual-shirt-pant-set',
                'short_description' => 'রোজকার ব্যবহারের জন্য আরামদায়ক শার্ট-প্যান্ট সেট',
                'description' => 'নরম কটন ব্লেন্ড কাপড়ে তৈরি সেট, প্রতিদিনের স্কুল-পরবর্তী ও বেড়ানোর জন্য উপযুক্ত।',
                'is_bestseller' => true,
                'image' => 'boys-casual-set.jpg',
                'variants' => [
                    ['name' => '2-3 বছর (2-3 Years)', 'weight_kg' => 0.3, 'price' => 750, 'sale_price' => 650, 'stock' => 30],
                    ['name' => '4-5 বছর (4-5 Years)', 'weight_kg' => 0.35, 'price' => 850, 'sale_price' => 750, 'stock' => 25],
                ],
            ],
            // Diapers & Wipes
            [
                'category_slug' => 'diapers-wipes',
                'name' => 'প্রিমিয়াম বেবি ডায়াপার (Premium Baby Diapers)',
                'slug' => 'premium-baby-diapers',
                'short_description' => '১২ ঘণ্টা পর্যন্ত লিক-প্রুফ সুরক্ষা',
                'description' => 'অতিরিক্ত শোষণক্ষম ও নরম ডায়াপার, সারারাত সুরক্ষা দেয়। ত্বকের জন্য নিরাপদ, ব্রিদেবল লেয়ারযুক্ত।',
                'is_featured' => true,
                'is_bestseller' => true,
                'is_trending' => true,
                'image' => 'baby-diapers.jpg',
                'variants' => [
                    ['name' => 'S সাইজ - ৪-৮ কেজি, ৪৪ পিস (S - 4-8kg, 44pcs)', 'weight_kg' => 1.2, 'price' => 650, 'sale_price' => 600, 'stock' => 80],
                    ['name' => 'M সাইজ - ৬-১১ কেজি, ৪০ পিস (M - 6-11kg, 40pcs)', 'weight_kg' => 1.3, 'price' => 700, 'sale_price' => 650, 'stock' => 70],
                    ['name' => 'L সাইজ - ৯-১৪ কেজি, ৩৬ পিস (L - 9-14kg, 36pcs)', 'weight_kg' => 1.3, 'price' => 720, 'sale_price' => 680, 'stock' => 60],
                    ['name' => 'XL সাইজ - ১২-১৭ কেজি, ৩২ পিস (XL - 12-17kg, 32pcs)', 'weight_kg' => 1.4, 'price' => 750, 'sale_price' => 700, 'stock' => 45],
                ],
            ],
            [
                'category_slug' => 'diapers-wipes',
                'name' => 'সফট বেবি ওয়াইপস (Soft Baby Wipes)',
                'slug' => 'soft-baby-wipes',
                'short_description' => 'অ্যালকোহল-মুক্ত, ত্বকের জন্য নিরাপদ বেবি ওয়াইপস',
                'description' => '৯৯% পানি সমৃদ্ধ, সুগন্ধিমুক্ত ও অ্যালকোহল-মুক্ত ওয়াইপস। ডায়াপার পরিবর্তন ও হাত-মুখ মোছার জন্য আদর্শ।',
                'is_trending' => true,
                'image' => 'baby-wipes.jpg',
                'variants' => [
                    ['name' => '১ প্যাক - ৮০ পিস (1 Pack - 80pcs)', 'weight_kg' => 0.4, 'price' => 150, 'sale_price' => 130, 'stock' => 100],
                    ['name' => '৩ প্যাক কম্বো - ২৪০ পিস (3 Pack Combo - 240pcs)', 'weight_kg' => 1.1, 'price' => 420, 'sale_price' => 370, 'stock' => 50],
                ],
            ],
            // Feeding & Nursing
            [
                'category_slug' => 'feeding-nursing',
                'name' => 'এন্টি-কোলিক ফিডিং বোতল (Anti-Colic Feeding Bottle)',
                'slug' => 'anti-colic-feeding-bottle',
                'short_description' => 'গ্যাস ও পেটব্যথা কমাতে বিশেষ ভেন্ট সিস্টেম',
                'description' => 'BPA-মুক্ত ফুড-গ্রেড প্লাস্টিকে তৈরি, স্তনের মতো নিপলসহ। এন্টি-কোলিক ভালভ শিশুর গ্যাসের সমস্যা কমাতে সাহায্য করে।',
                'is_featured' => true,
                'image' => 'feeding-bottle.jpg',
                'variants' => [
                    ['name' => '১২৫ মিলি (125ml)', 'weight_kg' => 0.15, 'price' => 350, 'sale_price' => 300, 'stock' => 55],
                    ['name' => '২৫০ মিলি (250ml)', 'weight_kg' => 0.2, 'price' => 450, 'sale_price' => 400, 'stock' => 45],
                ],
            ],
            // Toys & Games
            [
                'category_slug' => 'toys-games',
                'name' => 'শিক্ষামূলক বিল্ডিং ব্লক সেট (Educational Building Blocks Set)',
                'slug' => 'educational-building-blocks-set',
                'short_description' => 'শিশুর সৃজনশীলতা বাড়াতে নিরাপদ বিল্ডিং ব্লক',
                'description' => 'নন-টক্সিক প্লাস্টিকে তৈরি রঙিন ব্লক, হাত-চোখের সমন্বয় ও কল্পনাশক্তি বাড়াতে সাহায্য করে। ৩ বছর ও তদূর্ধ্ব বয়সের জন্য উপযুক্ত।',
                'is_featured' => true,
                'is_bestseller' => true,
                'image' => 'building-blocks.jpg',
                'variants' => [
                    ['name' => '৫০ পিস (50 Pieces)', 'weight_kg' => 0.5, 'price' => 650, 'sale_price' => 580, 'stock' => 40],
                    ['name' => '১০০ পিস (100 Pieces)', 'weight_kg' => 0.9, 'price' => 1100, 'sale_price' => 980, 'stock' => 25],
                ],
            ],
            [
                'category_slug' => 'toys-games',
                'name' => 'রিমোট কন্ট্রোল কার (Remote Control Car)',
                'slug' => 'remote-control-car',
                'short_description' => 'রিচার্জেবল হাই-স্পিড আরসি কার',
                'description' => 'রিচার্জেবল ব্যাটারিচালিত রিমোট কন্ট্রোল কার, অফ-রোড হুইলসহ। শীঘ্রই স্টকে আসছে — আগাম অর্ডার করে রাখুন।',
                'is_trending' => true,
                'is_preorder' => true,
                'image' => 'rc-car.jpg',
                'variants' => [
                    ['name' => 'ছোট মডেল (Small Model)', 'weight_kg' => 0.6, 'price' => 1200, 'sale_price' => null, 'stock' => 0],
                    ['name' => 'বড় মডেল (Large Model)', 'weight_kg' => 1.1, 'price' => 1800, 'sale_price' => null, 'stock' => 0],
                ],
            ],
            // Baby Care
            [
                'category_slug' => 'baby-care',
                'name' => 'বেবি লোশন ও অয়েল কম্বো (Baby Lotion & Oil Combo)',
                'slug' => 'baby-lotion-oil-combo',
                'short_description' => 'নবজাতকের কোমল ত্বকের যত্নে ডার্মাটোলজিক্যালি টেস্টেড',
                'description' => 'প্যারাবেন-মুক্ত ও ডার্মাটোলজিক্যালি টেস্টেড লোশন ও অয়েল কম্বো। প্রতিদিনের মালিশ ও ময়েশ্চারাইজিংয়ের জন্য উপযুক্ত।',
                'is_featured' => true,
                'image' => 'baby-lotion.jpg',
                'variants' => [
                    ['name' => '১০০ মিলি প্রতিটি (100ml Each)', 'weight_kg' => 0.25, 'price' => 320, 'sale_price' => 280, 'stock' => 60],
                    ['name' => '২০০ মিলি প্রতিটি (200ml Each)', 'weight_kg' => 0.45, 'price' => 550, 'sale_price' => 480, 'stock' => 40],
                ],
            ],
            // Nursery & Bedding
            [
                'category_slug' => 'nursery-bedding',
                'name' => 'বেবি ক্রিব উইথ মশারি (Baby Crib with Mosquito Net)',
                'slug' => 'baby-crib-with-mosquito-net',
                'short_description' => 'নিরাপদ ও আরামদায়ক বেবি ক্রিব, মশারিসহ',
                'description' => 'শক্তপোক্ত কাঠামো ও উচ্চতা সমন্বয়যোগ্য বেবি ক্রিব, পূর্ণ ঢাকনা মশারিসহ আসে। ০-২ বছর বয়সীদের জন্য উপযুক্ত।',
                'is_bestseller' => true,
                'image' => 'baby-crib.jpg',
                'variants' => [
                    ['name' => 'স্ট্যান্ডার্ড (Standard, 0-2 Years)', 'weight_kg' => 8, 'price' => 4800, 'sale_price' => 4300, 'stock' => 15],
                ],
            ],
            // School & Stationery
            [
                'category_slug' => 'school-stationery',
                'name' => 'কিডস স্কুল ব্যাকপ্যাক (Kids School Backpack)',
                'slug' => 'kids-school-backpack',
                'short_description' => 'হালকা ওজনের ও পানি-প্রতিরোধী স্কুল ব্যাগ',
                'description' => 'এরগনোমিক স্ট্র্যাপ ও একাধিক কম্পার্টমেন্টসহ পানি-প্রতিরোধী ব্যাগ। প্রি-স্কুল থেকে প্রাইমারি বয়সের বাচ্চাদের জন্য।',
                'is_trending' => true,
                'image' => 'kids-backpack.jpg',
                'variants' => [
                    ['name' => 'ছোট - নার্সারি/কেজি (Small - Nursery/KG)', 'weight_kg' => 0.4, 'price' => 750, 'sale_price' => 650, 'stock' => 45],
                    ['name' => 'বড় - ক্লাস ১-৫ (Large - Class 1-5)', 'weight_kg' => 0.55, 'price' => 950, 'sale_price' => 850, 'stock' => 35],
                ],
            ],
        ];

        foreach ($products as $data) {
            $category = Category::where('slug', $data['category_slug'])->first();
            $variants = $data['variants'];
            unset($data['category_slug'], $data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $category->id,
                    'is_featured' => $data['is_featured'] ?? false,
                    'is_bestseller' => $data['is_bestseller'] ?? false,
                    'is_trending' => $data['is_trending'] ?? false,
                    'is_preorder' => $data['is_preorder'] ?? false,
                ])
            );

            foreach ($variants as $i => $variant) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'sort_order' => $i],
                    array_merge($variant, [
                        'sku' => strtoupper(Str::slug($product->slug.'-'.($i + 1))),
                    ])
                );
            }
        }

        // Sample reviews, admin-authored so a fresh install already has
        // something in the homepage carousel. Real ones come from a
        // delivered order (Customer > Orders) and wait for approval.
        $reviews = [
            ['slug' => 'newborn-cotton-romper-set', 'customer_name' => 'Farzana Akter', 'rating' => 5, 'title' => 'Super soft fabric', 'body' => 'কাপড়টা অনেক নরম, আমার বাবুর ত্বকে কোনো র‍্যাশ হয়নি। সাইজও একদম পারফেক্ট।'],
            ['slug' => 'premium-baby-diapers', 'customer_name' => 'Sadia Islam', 'rating' => 5, 'title' => 'No leaks overnight', 'body' => 'সারারাত ব্যবহার করেও লিক হয়নি। দামের তুলনায় মানটা সত্যিই ভালো।'],
            ['slug' => 'educational-building-blocks-set', 'customer_name' => 'Tanvir Ahmed', 'rating' => 4, 'title' => 'Kids love it', 'body' => 'My 4-year-old plays with this every day. Pieces are sturdy and colours are bright.'],
            ['slug' => 'kids-school-backpack', 'customer_name' => 'Nusrat Jahan', 'rating' => 5, 'title' => null, 'body' => 'ওজনে হালকা কিন্তু বেশ শক্তপোক্ত। বৃষ্টিতেও ভেতরের বই ভেজেনি।'],
            ['slug' => 'anti-colic-feeding-bottle', 'customer_name' => 'Rakib Hasan', 'rating' => 4, 'title' => 'Works as promised', 'body' => 'Genuinely reduced gas issues for my baby. Easy to clean too.'],
        ];

        foreach ($reviews as $r) {
            $product = Product::where('slug', $r['slug'])->first();

            if (! $product) {
                continue;
            }

            Review::updateOrCreate(
                ['product_id' => $product->id, 'customer_name' => $r['customer_name']],
                [
                    'rating' => $r['rating'],
                    'title' => $r['title'],
                    'body' => $r['body'],
                    'is_approved' => true,
                ]
            );
        }

        // Static pages the footer links to.
        $this->call(PageSeeder::class);
    }
}
