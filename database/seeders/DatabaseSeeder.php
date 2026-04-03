<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'              => 'Admin',
                'password'          => bcrypt('111111'),
                'email_verified_at' => now(),
            ]
        );

        // Suppliers
        Supplier::updateOrCreate(
            ['phone' => '01700000001'],
            [
                'name'           => 'Garden Fresh Supplying Co.',
                'contact_person' => 'Mr. Rahim',
                'email'          => 'rahim@gardenfresh.com',
                'address'        => 'Chapainawabganj, Bangladesh',
            ]
        );

        // Categories
        $categories = [
            ['name' => 'আম (Mangoes)', 'slug' => 'mangoes', 'description' => 'চাঁপাই নবাবগঞ্জের সেরা আম - Garden fresh mangoes from Chapainawabganj', 'sort_order' => 1],
            ['name' => 'খেজুর গুড় (Date Palm Jaggery)', 'slug' => 'date-palm-jaggery', 'description' => 'রাজশাহীর খাঁটি খেজুরের গুড় - Pure date palm jaggery from Rajshahi', 'sort_order' => 2],
            ['name' => 'ঘি (Ghee)', 'slug' => 'ghee', 'description' => 'খাঁটি গাওয়া ঘি - Pure cow ghee', 'sort_order' => 3],
            ['name' => 'সরিষার তেল (Mustard Oil)', 'slug' => 'mustard-oil', 'description' => 'ঘানিভাঙ্গা খাঁটি সরিষার তেল - Cold-pressed pure mustard oil', 'sort_order' => 4],
            ['name' => 'আমসত্ত্ব (Mango Bar)', 'slug' => 'mango-bar', 'description' => 'হাতে তৈরি আমসত্ত্ব - Handmade mango leather/bar', 'sort_order' => 5],
            ['name' => 'মধু (Honey)', 'slug' => 'honey', 'description' => 'সুন্দরবনের খাঁটি মধু - Pure Sundarbans honey', 'sort_order' => 6],
            ['name' => 'মৌসুমী ফল (Seasonal Fruits)', 'slug' => 'seasonal-fruits', 'description' => 'দেশি মৌসুমী তাজা ফল - Fresh seasonal local fruits', 'sort_order' => 7],
            ['name' => 'মশলা ও অন্যান্য (Spices & Others)', 'slug' => 'spices-others', 'description' => 'দেশি মশলা ও অন্যান্য অর্গানিক পণ্য - Local spices and organic products', 'sort_order' => 8],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Products with Variants
        $products = [
            // Mangoes
            [
                'category_slug'     => 'mangoes',
                'name'              => 'কাটিমন আম (Katimon Mango)',
                'slug'              => 'katimon-mango',
                'short_description' => 'চাঁপাই নবাবগঞ্জের বিখ্যাত কাটিমন আম',
                'description'       => 'চাঁপাই নবাবগঞ্জের গার্ডেন থেকে সরাসরি সংগ্রহ করা কাটিমন আম। সম্পূর্ণ প্রাকৃতিক ও ফরমালিনমুক্ত। মিষ্টি স্বাদ ও অসাধারণ সুগন্ধযুক্ত।',
                'is_featured'       => true,
                'is_bestseller'     => true,
                'is_trending'       => true,
                'image'             => 'himsagar.png',
                'variants'          => [
                    ['name' => '৬ কেজি (6 kg)', 'weight_kg' => 6, 'price' => 2600, 'sale_price' => 2500, 'stock' => 50],
                    ['name' => '১২ কেজি (12 kg)', 'weight_kg' => 12, 'price' => 5000, 'sale_price' => 4800, 'stock' => 30],
                    ['name' => '২৪ কেজি (24 kg)', 'weight_kg' => 24, 'price' => 10000, 'sale_price' => 9500, 'stock' => 20],
                ],
            ],
            [
                'category_slug'     => 'mangoes',
                'name'              => 'হিমসাগর আম (Himsagor Mango)',
                'slug'              => 'himsagor-mango',
                'short_description' => 'রাজশাহীর মিষ্টি হিমসাগর আম',
                'description'       => 'রাজশাহীর সেরা হিমসাগর আম। অত্যন্ত মিষ্টি ও রসালো। গাছপাকা ও ফরমালিনমুক্ত।',
                'is_featured'       => true,
                'is_bestseller'     => true,
                'image'             => 'himsagar.png',
                'variants'          => [
                    ['name' => '৬ কেজি (6 kg)', 'weight_kg' => 6, 'price' => 1800, 'sale_price' => 1690, 'stock' => 40],
                    ['name' => '১২ কেজি (12 kg)', 'weight_kg' => 12, 'price' => 3500, 'sale_price' => 3200, 'stock' => 25],
                ],
            ],
            [
                'category_slug'     => 'mangoes',
                'name'              => 'গৌড়মতি আম (Gourmoti Mango)',
                'slug'              => 'gourmoti-mango',
                'short_description' => 'প্রিমিয়াম গৌড়মতি আম',
                'description'       => 'গৌড়মতি আম বাংলাদেশের সবচেয়ে দামি ও সুস্বাদু আমের একটি। অসাধারণ মিষ্টি ও সৌরভযুক্ত।',
                'is_featured'       => true,
                'is_trending'       => true,
                'variants'          => [
                    ['name' => '৬ কেজি (6 kg)', 'weight_kg' => 6, 'price' => 1800, 'sale_price' => 1400, 'stock' => 35],
                    ['name' => '১২ কেজি (12 kg)', 'weight_kg' => 12, 'price' => 3200, 'sale_price' => 2800, 'stock' => 20],
                    ['name' => '২৫ কেজি (25 kg)', 'weight_kg' => 25, 'price' => 5500, 'sale_price' => 5000, 'stock' => 10],
                ],
            ],
            [
                'category_slug'     => 'mangoes',
                'name'              => 'আম্রপালি আম (Amrapali Mango)',
                'slug'              => 'amrapali-mango',
                'short_description' => 'মিষ্টি আম্রপালি আম',
                'description'       => 'আম্রপালি আমের বিশেষত্ব হলো এর গাঢ় কমলা মাংস ও অত্যন্ত মিষ্টি স্বাদ।',
                'is_bestseller'     => true,
                'variants'          => [
                    ['name' => '১২ কেজি (12 kg)', 'weight_kg' => 12, 'price' => 2800, 'sale_price' => 2350, 'stock' => 30],
                    ['name' => '২৫ কেজি (25 kg)', 'weight_kg' => 25, 'price' => 5000, 'sale_price' => 4800, 'stock' => 15],
                ],
            ],
            [
                'category_slug'     => 'mangoes',
                'name'              => 'কাঁচা টক আম (Green Sour Mango)',
                'slug'              => 'green-sour-mango',
                'short_description' => 'আচার ও ভর্তার জন্য কাঁচা টক আম',
                'description'       => 'কাঁচা টক আম - আচার, ভর্তা, চাটনি তৈরির জন্য। সম্পূর্ণ তাজা ও অর্গানিক।',
                'is_trending'       => true,
                'variants'          => [
                    ['name' => '৩ কেজি (3 kg)', 'weight_kg' => 3, 'price' => 1200, 'sale_price' => 1000, 'stock' => 60],
                    ['name' => '৬ কেজি (6 kg)', 'weight_kg' => 6, 'price' => 2400, 'sale_price' => 2000, 'stock' => 40],
                ],
            ],
            // Date Palm Jaggery
            [
                'category_slug'     => 'date-palm-jaggery',
                'name'              => 'খাঁটি খেজুর গুড় (Pure Date Palm Jaggery)',
                'slug'              => 'pure-date-palm-jaggery',
                'short_description' => 'রাজশাহীর খাঁটি খেজুরের গুড়',
                'description'       => 'শীতে চলে বাঙালির উৎসবের পিঠা, পায়েশ কিংবা ভাপা পিঠায় রাখুন রাজশাহীর খাঁটি খেজুরের গুড়। ১০০% খাঁটি, কোনো মেশাল নেই।',
                'is_featured'       => true,
                'is_bestseller'     => true,
                'image'             => 'khejur-gur.png',
                'variants'          => [
                    ['name' => '১ কেজি (1 kg)', 'weight_kg' => 1, 'price' => 500, 'sale_price' => 450, 'stock' => 100],
                    ['name' => '২ কেজি (2 kg)', 'weight_kg' => 2, 'price' => 1000, 'sale_price' => 880, 'stock' => 60],
                    ['name' => '৫ কেজি (5 kg)', 'weight_kg' => 5, 'price' => 2500, 'sale_price' => 2100, 'stock' => 30],
                ],
            ],
            // Ghee
            [
                'category_slug'     => 'ghee',
                'name'              => 'খাঁটি গাওয়া ঘি (Pure Cow Ghee)',
                'slug'              => 'pure-cow-ghee',
                'short_description' => 'দেশি গরুর দুধের খাঁটি ঘি',
                'description'       => 'দেশি গরুর দুধ থেকে তৈরি ১০০% খাঁটি ঘি। রান্না, পোলাও, বিরিয়ানি ও পিঠা তৈরিতে অসাধারণ স্বাদ আনে।',
                'is_featured'       => true,
                'image'             => 'cow-ghee.png',
                'variants'          => [
                    ['name' => '৫০০ গ্রাম (500g)', 'weight_kg' => 0.5, 'price' => 700, 'sale_price' => 650, 'stock' => 80],
                    ['name' => '১ কেজি (1 kg)', 'weight_kg' => 1, 'price' => 1400, 'sale_price' => 1250, 'stock' => 50],
                ],
            ],
            // Mustard Oil
            [
                'category_slug'     => 'mustard-oil',
                'name'              => 'ঘানিভাঙ্গা সরিষার তেল (Cold-Pressed Mustard Oil)',
                'slug'              => 'cold-pressed-mustard-oil',
                'short_description' => 'ঘানিভাঙ্গা খাঁটি সরিষার তেল',
                'description'       => 'ঘানিতে ভাঙ্গা ১০০% খাঁটি সরিষার তেল। কোনো কেমিক্যাল প্রসেসিং নেই।',
                'is_featured'       => true,
                'is_trending'       => true,
                'image'             => 'mustard-oil-bottle.png',
                'variants'          => [
                    ['name' => '১ লিটার (1 liter)', 'weight_kg' => 1, 'price' => 400, 'sale_price' => 350, 'stock' => 100],
                    ['name' => '৫ লিটার (5 liters)', 'weight_kg' => 5, 'price' => 1800, 'sale_price' => 1600, 'stock' => 40],
                ],
            ],
            // Mango Bar
            [
                'category_slug'     => 'mango-bar',
                'name'              => 'আমসত্ত্ব (Mango Bar / Mango Leather)',
                'slug'              => 'mango-bar-leather',
                'short_description' => 'হাতে তৈরি ঐতিহ্যবাহী আমসত্ত্ব',
                'description'       => 'চাঁপাই নবাবগঞ্জের আম দিয়ে তৈরি ঐতিহ্যবাহী আমসত্ত্ব। টক-মিষ্টি ও মিষ্টি দুই ধরনেই পাওয়া যায়।',
                'is_bestseller'     => true,
                'variants'          => [
                    ['name' => 'টক-মিষ্টি ১ কেজি (Sweet-Sour 1kg)', 'weight_kg' => 1, 'price' => 750, 'sale_price' => 670, 'stock' => 50],
                    ['name' => 'মিষ্টি ১ কেজি (Sweet 1kg)', 'weight_kg' => 1, 'price' => 750, 'sale_price' => 670, 'stock' => 50],
                ],
            ],
            // Honey
            [
                'category_slug'     => 'honey',
                'name'              => 'সুন্দরবনের খাঁটি মধু (Pure Sundarbans Honey)',
                'slug'              => 'pure-sundarbans-honey',
                'short_description' => 'সুন্দরবনের খাঁটি মধু',
                'description'       => 'সুন্দরবন থেকে সরাসরি সংগ্রহ করা ১০০% খাঁটি মধু। কোনো চিনি বা কেমিক্যাল মেশানো নেই।',
                'is_featured'       => true,
                'is_trending'       => true,
                'image'             => 'sundarban-honey.png',
                'variants'          => [
                    ['name' => '৫০০ গ্রাম (500g)', 'weight_kg' => 0.5, 'price' => 600, 'sale_price' => 550, 'stock' => 70],
                    ['name' => '১ কেজি (1 kg)', 'weight_kg' => 1, 'price' => 1100, 'sale_price' => 1000, 'stock' => 40],
                ],
            ],
            // Seasonal Fruits
            [
                'category_slug'     => 'seasonal-fruits',
                'name'              => 'লিচু (Litchi)',
                'slug'              => 'litchi',
                'short_description' => 'দিনাজপুরের মিষ্টি লিচু',
                'description'       => 'দিনাজপুরের বিখ্যাত মিষ্টি লিচু। গাছপাকা ও তাজা।',
                'is_trending'       => true,
                'is_preorder'       => true,
                'variants'          => [
                    ['name' => '৩ কেজি (3 kg)', 'weight_kg' => 3, 'price' => 600, 'sale_price' => null, 'stock' => 0],
                    ['name' => '৬ কেজি (6 kg)', 'weight_kg' => 6, 'price' => 1100, 'sale_price' => null, 'stock' => 0],
                ],
            ],
            // Spices
            [
                'category_slug'     => 'spices-others',
                'name'              => 'দেশি হলুদ গুঁড়া (Pure Turmeric Powder)',
                'slug'              => 'pure-turmeric-powder',
                'short_description' => 'খাঁটি দেশি হলুদ গুঁড়া',
                'description'       => 'ঘানিতে ভাঙ্গা খাঁটি দেশি হলুদের গুঁড়া। কোনো মেশাল নেই।',
                'variants'          => [
                    ['name' => '৫০০ গ্রাম (500g)', 'weight_kg' => 0.5, 'price' => 300, 'sale_price' => 250, 'stock' => 80],
                    ['name' => '১ কেজি (1 kg)', 'weight_kg' => 1, 'price' => 550, 'sale_price' => 480, 'stock' => 50],
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
                    'category_id'   => $category->id,
                    'is_featured'   => $data['is_featured'] ?? false,
                    'is_bestseller' => $data['is_bestseller'] ?? false,
                    'is_trending'   => $data['is_trending'] ?? false,
                    'is_preorder'   => $data['is_preorder'] ?? false,
                ])
            );

            foreach ($variants as $i => $variant) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'sort_order' => $i],
                    array_merge($variant, [
                        'sku'        => strtoupper(Str::slug($product->slug . '-' . ($i + 1))),
                    ])
                );
            }
        }

        // --- Pages Seeding ---
        $pages = [
            [
                'slug'       => 'about-us',
                'title_en'   => 'About Mango Hut',
                'title_bn'   => 'ম্যাংগো হাট সম্পর্কে',
                'content_en' => '<h3>Our Journey</h3><p>At Mango Hut, we believe in the purity of nature. Born in the heart of Chapainawabganj, the mango capital of Bangladesh, we started with a simple mission: to bring the authentic, garden-fresh taste of premium mangoes directly to your table.</p><h3>Why Choose Us?</h3><ul><li><strong>100% Organic:</strong> No formalin or harmful chemicals.</li><li><strong>Direct Sourcing:</strong> Straight from our monitored orchards.</li><li><strong>Premium Quality:</strong> Every fruit is hand-picked for perfection.</li></ul>',
                'content_bn' => '<h3>আমাদের যাত্রা</h3><p>ম্যাংগো হাটে আমরা প্রকৃতির বিশুদ্ধতায় বিশ্বাস করি। বাংলাদেশের আমের রাজধানী চাঁপাইনবাবগঞ্জের প্রাণকেন্দ্রে আমাদের যাত্রা শুরু। আমাদের লক্ষ্য ছিল সাধারণ: প্রিমিয়াম আমের আসল, বাগান-তাজা স্বাদ সরাসরি আপনার টেবিলে পৌঁছে দেওয়া।</p><h3>কেন আমাদের বেছে নেবেন?</h3><ul><li><strong>১০০% অর্গানিক:</strong> কোনো ফরমালিন বা ক্ষতিকারক রাসায়নিক নেই।</li><li><strong>সরাসরি সংগ্রহ:</strong> আমাদের নিজস্ব তত্ত্বাবধানে থাকা বাগান থেকে সরাসরি।</li><li><strong>সেরা মান:</strong> প্রতিটি ফল নিখুঁতভাবে হাতে বাছাই করা হয়।</li></ul>',
            ],
            [
                'slug'       => 'terms-and-conditions',
                'title_en'   => 'Terms & Conditions',
                'title_bn'   => 'টার্মস ও কন্ডিশন',
                'content_en' => '<h3>1. Agreement to Terms</h3><p>By using Mango Hut, you agree to comply with our service policies. We strive to provide the best organic products, but availability depends on seasonal harvests.</p><h3>2. Ordering & Payment</h3><p>Orders are confirmed after verification. Payments can be made via Cash on Delivery or digital payment gateways.</p>',
                'content_bn' => '<h3>১. শর্তাবলী সম্মতি</h3><p>ম্যাংগো হাট ব্যবহারের মাধ্যমে আপনি আমাদের পরিষেবা নীতি মেনে চলতে সম্মত হন। আমরা সেরা অর্গানিক পণ্য প্রদানের চেষ্টা করি, তবে প্রাপ্যতা মৌসুমী ফসলের ওপর নির্ভর করে।</p><h3>২. অর্ডার এবং পেমেন্ট</h3><p>যাচাইকরণের পরে অর্ডার নিশ্চিত করা হয়। পেমেন্ট ক্যাশ অন ডেলিভারি বা ডিজিটাল পেমেন্ট গেটওয়ের মাধ্যমে করা যেতে পারে।</p>',
            ],
            [
                'slug'       => 'privacy-policy',
                'title_en'   => 'Privacy Policy',
                'title_bn'   => 'প্রাইভেসি পলিসি',
                'content_en' => '<h3>Data Collection</h3><p>We respect your privacy. We only collect necessary information for order processing and delivery. Your data is never shared with third parties for marketing purposes.</p>',
                'content_bn' => '<h3>তথ্য সংগ্রহ</h3><p>আমরা আপনার গোপনীয়তাকে সম্মান করি। আমরা কেবল অর্ডার প্রসেসিং এবং ডেলিভারির জন্য প্রয়োজনীয় তথ্য সংগ্রহ করি। আপনার তথ্য কখনোই মার্কেটিং উদ্দেশ্যে তৃতীয় পক্ষের সাথে শেয়ার করা হয় না।</p>',
            ],
            [
                'slug'       => 'shipping-policy',
                'title_en'   => 'Shipping Policy',
                'title_bn'   => 'শিপিং পলিসি',
                'content_en' => '<h3>Delivery Times</h3><p>Internal city delivery takes 24-48 hours. Nationwide delivery takes 3-5 days. Mangoes are shipped in specialized crates to ensure freshness.</p><h3>Free Delivery</h3><p>Free delivery is available on orders above the specified threshold (currently ৳2,000).</p>',
                'content_bn' => '<h3>ডেলিভারি সময়</h3><p>শহরের অভ্যন্তরে ডেলিভারি ২৪-৪৮ ঘণ্টা সময় নেয়। সারাদেশে ডেলিভারি হতে ৩-৫ দিন সময় লাগে। সতেজতা নিশ্চিত করতে আম বিশেষ ক্র্যাটে পাঠানো হয়।</p><h3>ফ্রি ডেলিভারি</h3><p>নির্দিষ্ট পরিমাণের বেশি অর্ডারে (বর্তমানে ২,০০০ টাকা) ফ্রি ডেলিভারি উপলব্ধ।</p>',
            ],
        ];

        foreach ($pages as $page) {
            \App\Models\Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
