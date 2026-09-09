<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The footer links to these five pages, so a fresh install needs them to exist
 * or every one of those links is a 404.
 *
 * Existing rows are left alone — this only fills in what is missing, and the
 * text is a starting point the admin is meant to edit.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            // firstOrCreate, not updateOrCreate: once an admin has edited a
            // page, re-running the seeder must not throw their words away.
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_active' => true]
            );
        }
    }

    private function pages(): array
    {
        return [
            [
                'slug' => 'return-policy',
                'title_en' => 'Return Policy',
                'title_bn' => 'রিটার্ন পলিসি',
                'content_en' => <<<'HTML'
                        <h2>Our promise</h2>
                        <p>We check every order before it leaves us so it reaches your little one exactly as expected. If something still arrives wrong, tell us and we will put it right.</p>

                        <h3>When you can return an item</h3>
                        <ul>
                        <li>The product arrived damaged or defective.</li>
                        <li>You received the wrong item, size or colour.</li>
                        <li>The packaging seal was broken on delivery.</li>
                        </ul>

                        <h3>How long you have</h3>
                        <p>Tell us within <strong>7 days</strong> of delivery for clothing, toys and gear, and within <strong>24 hours</strong> for diapers, wipes and feeding items due to hygiene reasons.</p>

                        <h3>What we cannot take back</h3>
                        <ul>
                        <li>Items that have been used, washed or have tags removed, unless they were faulty.</li>
                        <li>Diapers, wipes and other hygiene products once opened.</li>
                        <li>Orders where the delivery was refused without a reason.</li>
                        </ul>

                        <h3>How to raise a return</h3>
                        <ol>
                        <li>Message us on WhatsApp or call the number in the footer, with your order number.</li>
                        <li>Send a photo of the item and its packaging.</li>
                        <li>We reply within one working day with a replacement, a store credit or a refund — your choice.</li>
                        </ol>

                        <h3>Refunds</h3>
                        <p>Refunds go back the way you paid. Cash on delivery orders are refunded through bKash, Nagad or Rocket within 3–5 working days of the return being approved. Delivery charges are refunded when the fault was ours.</p>
                        HTML,
                'content_bn' => <<<'HTML'
                        <h2>আমাদের প্রতিশ্রুতি</h2>
                        <p>প্রতিটি অর্ডার পাঠানোর আগে আমরা যাচাই করি, যেন তা আপনার সোনামণির কাছে ঠিকভাবে পৌঁছায়। তারপরও কোনো ভুল হলে আমাদের জানান — আমরা সমাধান করে দেব।</p>

                        <h3>কখন ফেরত দিতে পারবেন</h3>
                        <ul>
                        <li>পণ্য ভাঙা বা ত্রুটিপূর্ণ অবস্থায় পৌঁছেছে।</li>
                        <li>ভুল পণ্য, ভুল সাইজ বা ভুল রং পেয়েছেন।</li>
                        <li>ডেলিভারির সময় প্যাকেটের সিল ভাঙা ছিল।</li>
                        </ul>

                        <h3>কত সময়ের মধ্যে</h3>
                        <p>পোশাক, খেলনা ও অন্যান্য সামগ্রীর ক্ষেত্রে ডেলিভারির <strong>৭ দিনের</strong> মধ্যে, আর ডায়াপার, ওয়াইপস ও ফিডিং পণ্যের ক্ষেত্রে স্বাস্থ্যগত কারণে <strong>২৪ ঘণ্টার</strong> মধ্যে জানাতে হবে।</p>

                        <h3>যা ফেরত নেওয়া হয় না</h3>
                        <ul>
                        <li>ব্যবহৃত, ধোয়া বা ট্যাগ খোলা পণ্য, যদি না সেটি ত্রুটিপূর্ণ হয়।</li>
                        <li>খোলা ডায়াপার, ওয়াইপস বা অন্যান্য হাইজিন পণ্য।</li>
                        <li>কারণ ছাড়া ডেলিভারি ফিরিয়ে দেওয়া অর্ডার।</li>
                        </ul>

                        <h3>যেভাবে রিটার্ন করবেন</h3>
                        <ol>
                        <li>অর্ডার নম্বরসহ হোয়াটসঅ্যাপে মেসেজ করুন বা ফুটারের নম্বরে কল করুন।</li>
                        <li>পণ্য ও প্যাকেটের ছবি পাঠান।</li>
                        <li>এক কর্মদিবসের মধ্যে আমরা রিপ্লেসমেন্ট, স্টোর ক্রেডিট বা রিফান্ড — আপনার পছন্দ অনুযায়ী ব্যবস্থা করব।</li>
                        </ol>

                        <h3>রিফান্ড</h3>
                        <p>যেভাবে পেমেন্ট করেছেন সেভাবেই রিফান্ড ফেরত যাবে। ক্যাশ অন ডেলিভারির ক্ষেত্রে রিটার্ন অনুমোদনের ৩–৫ কর্মদিবসের মধ্যে বিকাশ, নগদ বা রকেটে পাঠানো হয়। ভুল আমাদের হলে ডেলিভারি চার্জও ফেরত দেওয়া হয়।</p>
                        HTML,
            ],

            [
                'slug' => 'about-us',
                'title_en' => 'About BaburhashiBD',
                'title_bn' => 'BaburhashiBD সম্পর্কে',
                'content_en' => '<h3>Our Journey</h3><p>BaburhashiBD started with a simple idea: shopping for your little one should be easy, safe and joyful. From baby clothing and diapers to toys and school supplies, we bring everything a growing child needs to one place.</p><h3>Why Choose Us?</h3><ul><li><strong>Safety First:</strong> Every product is checked for quality before it reaches you.</li><li><strong>Wide Range:</strong> From newborn essentials to school-age needs.</li><li><strong>Nationwide Delivery:</strong> From our store to your doorstep, anywhere in Bangladesh.</li></ul>',
                'content_bn' => '<h3>আমাদের যাত্রা</h3><p>BaburhashiBD শুরু হয়েছিল একটি সাধারণ ভাবনা থেকে — আপনার ছোট্ট সোনামণির জন্য কেনাকাটা হোক সহজ, নিরাপদ ও আনন্দময়। শিশুর পোশাক ও ডায়াপার থেকে শুরু করে খেলনা ও স্কুল সামগ্রী — সবকিছু আমরা এক জায়গায় নিয়ে এসেছি।</p><h3>কেন আমাদের বেছে নেবেন?</h3><ul><li><strong>নিরাপত্তা প্রথমে:</strong> প্রতিটি পণ্য আপনার কাছে পৌঁছানোর আগে মান যাচাই করা হয়।</li><li><strong>বিস্তৃত পরিসর:</strong> নবজাতকের প্রয়োজনীয় জিনিস থেকে শুরু করে স্কুলের সামগ্রী পর্যন্ত।</li><li><strong>সারাদেশে ডেলিভারি:</strong> আমাদের দোকান থেকে সরাসরি আপনার দরজায়, বাংলাদেশের যেকোনো প্রান্তে।</li></ul>',
            ],
            [
                'slug' => 'terms-and-conditions',
                'title_en' => 'Terms & Conditions',
                'title_bn' => 'টার্মস ও কন্ডিশন',
                'content_en' => '<h3>1. Agreement to Terms</h3><p>By using BaburhashiBD, you agree to comply with our service policies. Product images are for illustration; actual colour or packaging may vary slightly.</p><h3>2. Ordering & Payment</h3><p>Orders are confirmed after verification. Payments can be made via Cash on Delivery or digital payment gateways.</p>',
                'content_bn' => '<h3>১. শর্তাবলী সম্মতি</h3><p>BaburhashiBD ব্যবহারের মাধ্যমে আপনি আমাদের পরিষেবা নীতি মেনে চলতে সম্মত হন। পণ্যের ছবি শুধুমাত্র উদাহরণস্বরূপ, প্রকৃত রং বা প্যাকেজিং কিছুটা ভিন্ন হতে পারে।</p><h3>২. অর্ডার এবং পেমেন্ট</h3><p>যাচাইকরণের পরে অর্ডার নিশ্চিত করা হয়। পেমেন্ট ক্যাশ অন ডেলিভারি বা ডিজিটাল পেমেন্ট গেটওয়ের মাধ্যমে করা যেতে পারে।</p>',
            ],
            [
                'slug' => 'privacy-policy',
                'title_en' => 'Privacy Policy',
                'title_bn' => 'প্রাইভেসি পলিসি',
                'content_en' => '<h3>Data Collection</h3><p>We respect your privacy. We only collect necessary information for order processing and delivery. Your data is never shared with third parties for marketing purposes.</p>',
                'content_bn' => '<h3>তথ্য সংগ্রহ</h3><p>আমরা আপনার গোপনীয়তাকে সম্মান করি। আমরা কেবল অর্ডার প্রসেসিং এবং ডেলিভারির জন্য প্রয়োজনীয় তথ্য সংগ্রহ করি। আপনার তথ্য কখনোই মার্কেটিং উদ্দেশ্যে তৃতীয় পক্ষের সাথে শেয়ার করা হয় না।</p>',
            ],
            [
                'slug' => 'shipping-policy',
                'title_en' => 'Shipping Policy',
                'title_bn' => 'শিপিং পলিসি',
                'content_en' => '<h3>Delivery Times</h3><p>Inside city delivery takes 24-48 hours. Nationwide delivery takes 3-5 days. Fragile items such as feeding bottles and toys are packed with extra padding.</p><h3>Free Delivery</h3><p>Free delivery is available on orders above the specified threshold (currently ৳2,000).</p>',
                'content_bn' => '<h3>ডেলিভারি সময়</h3><p>শহরের অভ্যন্তরে ডেলিভারি ২৪-৪৮ ঘণ্টা সময় নেয়। সারাদেশে ডেলিভারি হতে ৩-৫ দিন সময় লাগে। ফিডিং বোতল ও খেলনার মতো ভঙ্গুর পণ্য অতিরিক্ত প্যাডিং দিয়ে প্যাক করা হয়।</p><h3>ফ্রি ডেলিভারি</h3><p>নির্দিষ্ট পরিমাণের বেশি অর্ডারে (বর্তমানে ২,০০০ টাকা) ফ্রি ডেলিভারি উপলব্ধ।</p>',
            ],
        ];
    }
}
