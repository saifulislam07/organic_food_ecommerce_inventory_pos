<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The footer links to these pages, so a fresh install needs them to exist or
 * every one of those links is a 404.
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
                'content_en' => <<<'HTML'
                        <h2>1. Agreeing to these terms</h2>
                        <p>Using this site or placing an order means you accept what is written here. We may update these terms; the date at the top of this page tells you when they last changed.</p>

                        <h2>2. Products and prices</h2>
                        <ul>
                        <li>Product photos are for illustration. Colour and packaging can vary a little from what your screen shows.</li>
                        <li>Prices are in Bangladeshi Taka and include VAT where it applies.</li>
                        <li>We may change a price or withdraw a product at any time. An order already confirmed keeps the price it was placed at.</li>
                        <li>If an item is listed at an obviously wrong price, we will contact you before charging anything.</li>
                        </ul>

                        <h2>3. Placing an order</h2>
                        <p>An order is a request to buy, not a completed sale. It becomes a sale when we confirm it — usually by phone or SMS. We may decline an order if the item is out of stock, the address is outside our delivery area, or we cannot reach you to confirm.</p>

                        <h2>4. Pre-orders</h2>
                        <p>Some items may be ordered while they are out of stock. The conditions shown at the time — including how long the wait is expected to be — form part of these terms, and you are asked to accept them before the item goes in your cart.</p>

                        <h2>5. Payment</h2>
                        <p>You may pay cash on delivery, or through the digital payment methods shown at checkout. For cash on delivery, please have the exact amount ready.</p>

                        <h2>6. Delivery</h2>
                        <p>Delivery times are estimates, not guarantees. Weather, strikes and courier delays are outside our control, and we will tell you if your parcel is affected. Delivery charges are shown before you confirm the order.</p>

                        <h2>7. Cancellation, returns and refunds</h2>
                        <p>You may cancel before the parcel is handed to the courier. After that, our <a href="/return-policy">Return Policy</a> applies and forms part of these terms.</p>

                        <h2>8. Your account</h2>
                        <p>Keep your password to yourself — anything done through your account is treated as done by you. Tell us at once if you think somebody else has access.</p>

                        <h2>9. Using the site</h2>
                        <p>Please do not copy our photos or product text for another shop, attempt to break into the site, or place orders you do not intend to accept. We may close an account that does.</p>

                        <h2>10. What we are responsible for</h2>
                        <p>We are responsible for the goods being as described and reaching you in good condition. We are not responsible for losses beyond the value of the order itself. Nothing here removes rights the law gives you as a consumer.</p>

                        <h2>11. Getting in touch</h2>
                        <p>Any dispute is best sorted out by talking to us first. Call or message the number in the footer and we will try to settle it directly.</p>
                        HTML,
                'content_bn' => <<<'HTML'
                        <h2>১. শর্তে সম্মতি</h2>
                        <p>এই সাইট ব্যবহার করা বা অর্ডার করা মানে এখানে লেখা শর্তগুলো আপনি মেনে নিচ্ছেন। আমরা শর্ত হালনাগাদ করতে পারি; পাতার উপরে তারিখ দেখে বুঝবেন সর্বশেষ কবে বদলেছে।</p>

                        <h2>২. পণ্য ও দাম</h2>
                        <ul>
                        <li>পণ্যের ছবি উদাহরণস্বরূপ। স্ক্রিনে যা দেখছেন তার থেকে রং ও প্যাকেজিং সামান্য আলাদা হতে পারে।</li>
                        <li>দাম বাংলাদেশি টাকায়, এবং প্রযোজ্য ক্ষেত্রে ভ্যাটসহ।</li>
                        <li>যেকোনো সময় দাম বদলাতে বা পণ্য সরিয়ে নিতে পারি। নিশ্চিত হয়ে যাওয়া অর্ডার যে দামে হয়েছে সেই দামেই থাকবে।</li>
                        <li>কোনো পণ্যে স্পষ্টতই ভুল দাম বসে গেলে টাকা নেওয়ার আগে আমরা আপনাকে জানাব।</li>
                        </ul>

                        <h2>৩. অর্ডার করা</h2>
                        <p>অর্ডার মানে কেনার অনুরোধ, বিক্রি সম্পন্ন হওয়া নয়। আমরা নিশ্চিত করলে — সাধারণত ফোন বা এসএমএসে — তখনই বিক্রি হয়। পণ্য না থাকলে, ঠিকানা আমাদের ডেলিভারি এলাকার বাইরে হলে, বা আপনাকে ফোনে না পেলে অর্ডার বাতিল করতে পারি।</p>

                        <h2>৪. প্রি-অর্ডার</h2>
                        <p>কিছু পণ্য স্টকে না থাকা অবস্থাতেও অর্ডার করা যায়। তখন যে শর্তগুলো দেখানো হয় — কত দিন অপেক্ষা করতে হতে পারে সহ — সেগুলোও এই শর্তাবলীর অংশ, এবং কার্টে যোগ করার আগে সেগুলোতে রাজি হতে হয়।</p>

                        <h2>৫. পেমেন্ট</h2>
                        <p>ক্যাশ অন ডেলিভারিতে, অথবা চেকআউটে দেখানো ডিজিটাল পদ্ধতিতে পেমেন্ট করতে পারেন। ক্যাশ অন ডেলিভারির ক্ষেত্রে দয়া করে সঠিক পরিমাণ টাকা প্রস্তুত রাখুন।</p>

                        <h2>৬. ডেলিভারি</h2>
                        <p>ডেলিভারির সময় একটি ধারণা, নিশ্চয়তা নয়। আবহাওয়া, হরতাল ও কুরিয়ারের দেরি আমাদের হাতে নেই — আপনার পার্সেল আটকে গেলে আমরা জানাব। ডেলিভারি চার্জ অর্ডার নিশ্চিত করার আগেই দেখানো হয়।</p>

                        <h2>৭. বাতিল, রিটার্ন ও রিফান্ড</h2>
                        <p>কুরিয়ারে দেওয়ার আগ পর্যন্ত অর্ডার বাতিল করতে পারেন। তারপর আমাদের <a href="/return-policy">রিটার্ন পলিসি</a> প্রযোজ্য, যা এই শর্তাবলীর অংশ।</p>

                        <h2>৮. আপনার অ্যাকাউন্ট</h2>
                        <p>পাসওয়ার্ড নিজের কাছে রাখুন — আপনার অ্যাকাউন্ট থেকে যা করা হয় তা আপনারই করা ধরা হয়। অন্য কেউ ঢুকে পড়েছে মনে হলে সাথে সাথে জানান।</p>

                        <h2>৯. সাইট ব্যবহার</h2>
                        <p>আমাদের ছবি বা পণ্যের বিবরণ অন্য দোকানের জন্য কপি করবেন না, সাইটে অনুপ্রবেশের চেষ্টা করবেন না, আর যে অর্ডার নেবেন না সেটা করবেন না। এমন হলে অ্যাকাউন্ট বন্ধ করে দেওয়া হতে পারে।</p>

                        <h2>১০. আমাদের দায়</h2>
                        <p>পণ্য বর্ণনা অনুযায়ী হওয়া এবং ভালো অবস্থায় পৌঁছানোর দায়িত্ব আমাদের। অর্ডারের মূল্যের বেশি কোনো ক্ষতির দায় আমরা নিই না। ভোক্তা হিসেবে আইন আপনাকে যে অধিকার দেয়, এখানে কিছুই তা কেড়ে নেয় না।</p>

                        <h2>১১. যোগাযোগ</h2>
                        <p>যেকোনো অভিযোগ আগে আমাদের সাথে কথা বলেই মিটিয়ে নেওয়া ভালো। ফুটারের নম্বরে কল বা মেসেজ করুন, আমরা সরাসরি সমাধানের চেষ্টা করব।</p>
                        HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title_en' => 'Privacy Policy',
                'title_bn' => 'প্রাইভেসি পলিসি',
                'content_en' => <<<'HTML'
                        <h2>What we collect</h2>
                        <p>We ask for only what an order needs: your name, mobile number, delivery address and, if you make an account, your email. Payment card details never reach us — those stay with the payment provider.</p>

                        <h2>Why we collect it</h2>
                        <ul>
                        <li>To pack and deliver your order, and to call you if the address needs checking.</li>
                        <li>To send order updates by SMS or email.</li>
                        <li>To handle returns, refunds and warranty claims.</li>
                        <li>To keep our own records, as the law requires of a business.</li>
                        </ul>

                        <h2>Who else sees it</h2>
                        <p>Your name, number and address go to the courier carrying your parcel — they cannot deliver without it. Nothing goes to anybody else for advertising, and we do not sell customer lists.</p>

                        <h2>How long we keep it</h2>
                        <p>Order records are kept for our accounts. You can ask us to delete your account and its saved addresses at any time; order history that we are required to keep will remain, without being used to contact you.</p>

                        <h2>Cookies</h2>
                        <p>The site uses cookies to remember your cart and keep you signed in. Blocking them in your browser will stop the cart from working.</p>

                        <h2>Your choices</h2>
                        <ul>
                        <li>Ask what we hold about you, and ask us to correct it.</li>
                        <li>Ask us to delete your account.</li>
                        <li>Tell us to stop sending marketing messages — order updates will still come through.</li>
                        </ul>

                        <h2>Contact</h2>
                        <p>Message us on WhatsApp or call the number in the footer with any question about your data, and we will answer within two working days.</p>
                        HTML,
                'content_bn' => <<<'HTML'
                        <h2>আমরা কী কী তথ্য নিই</h2>
                        <p>অর্ডারের জন্য যতটুকু দরকার শুধু ততটুকুই — আপনার নাম, মোবাইল নম্বর, ডেলিভারি ঠিকানা, আর অ্যাকাউন্ট খুললে ইমেইল। কার্ডের তথ্য আমাদের কাছে আসে না, সেটা পেমেন্ট গেটওয়ের কাছেই থাকে।</p>

                        <h2>কেন নিই</h2>
                        <ul>
                        <li>অর্ডার প্যাক ও ডেলিভারি করতে, আর ঠিকানা যাচাইয়ের দরকার হলে ফোন করতে।</li>
                        <li>এসএমএস বা ইমেইলে অর্ডারের আপডেট পাঠাতে।</li>
                        <li>রিটার্ন, রিফান্ড ও ওয়ারেন্টির বিষয় সামলাতে।</li>
                        <li>ব্যবসার হিসাব সংরক্ষণে, যা আইন অনুযায়ী রাখতে হয়।</li>
                        </ul>

                        <h2>আর কে দেখতে পায়</h2>
                        <p>আপনার নাম, নম্বর ও ঠিকানা কুরিয়ারের কাছে যায় — এটা ছাড়া তারা পার্সেল পৌঁছাতে পারে না। বিজ্ঞাপনের জন্য অন্য কারও কাছে কিছু যায় না, আর আমরা কাস্টমারের তালিকা বিক্রি করি না।</p>

                        <h2>কতদিন রাখা হয়</h2>
                        <p>হিসাবের প্রয়োজনে অর্ডারের রেকর্ড রাখা হয়। আপনি যেকোনো সময় অ্যাকাউন্ট ও সেভ করা ঠিকানা মুছে ফেলতে বলতে পারেন; আইনত যে অর্ডার রেকর্ড রাখতে হয় সেটা থাকবে, তবে আপনার সাথে যোগাযোগে ব্যবহার হবে না।</p>

                        <h2>কুকি</h2>
                        <p>কার্ট মনে রাখা আর আপনাকে লগ-ইন অবস্থায় রাখার জন্য সাইটে কুকি ব্যবহার হয়। ব্রাউজারে কুকি বন্ধ করলে কার্ট কাজ করবে না।</p>

                        <h2>আপনার অধিকার</h2>
                        <ul>
                        <li>আপনার সম্পর্কে কী তথ্য আছে জানতে চাওয়া, আর ভুল থাকলে ঠিক করতে বলা।</li>
                        <li>অ্যাকাউন্ট মুছে ফেলতে বলা।</li>
                        <li>মার্কেটিং মেসেজ বন্ধ করতে বলা — অর্ডারের আপডেট তবু আসবে।</li>
                        </ul>

                        <h2>যোগাযোগ</h2>
                        <p>তথ্য নিয়ে যেকোনো প্রশ্নে ফুটারের নম্বরে হোয়াটসঅ্যাপ বা কল করুন, দুই কর্মদিবসের মধ্যে উত্তর দেব।</p>
                        HTML,
            ],
            [
                'slug' => 'faq',
                'title_en' => 'Frequently Asked Questions',
                'title_bn' => 'সাধারণ জিজ্ঞাসা',
                'content_en' => <<<'HTML'
                        <h2>Ordering</h2>

                        <h3>Do I need an account to order?</h3>
                        <p>No. You can check out as a guest with just your name, number and address. An account only saves you retyping them next time, and keeps your order history in one place.</p>

                        <h3>How do I know my order went through?</h3>
                        <p>You will see an order number on screen straight after checkout, and we send it by SMS. If neither arrived, the order did not reach us — please try again or call us.</p>

                        <h3>Can I change or cancel an order?</h3>
                        <p>Yes, as long as it has not been handed to the courier. Call or message us with your order number as soon as you can.</p>

                        <h2>Stock and pre-orders</h2>

                        <h3>An item says "Pre-order" — what does that mean?</h3>
                        <p>It is out of stock, but we are still taking orders for it. The conditions, including how long the wait is likely to be, are shown before you add it to the cart and you have to accept them to continue.</p>

                        <h3>When will a sold-out item come back?</h3>
                        <p>If it shows a pre-order button, the conditions on the product page give the expected wait. Otherwise, message us and we will tell you what we know.</p>

                        <h2>Delivery</h2>

                        <h3>How long does delivery take?</h3>
                        <p>Inside the city, usually 24–48 hours. Elsewhere in Bangladesh, 3–5 days. See our <a href="/shipping-policy">Shipping Policy</a> for the details.</p>

                        <h3>How much is delivery?</h3>
                        <p>The charge is shown at checkout before you confirm, and depends on whether the address is inside or outside Dhaka. Orders above the threshold shown in the top bar are delivered free.</p>

                        <h3>Do you deliver everywhere in Bangladesh?</h3>
                        <p>Yes, through our courier partners. Some remote areas take a little longer.</p>

                        <h2>Payment</h2>

                        <h3>Can I pay when the parcel arrives?</h3>
                        <p>Yes — cash on delivery is available on every order. Please keep the exact amount ready for the rider.</p>

                        <h3>What else can I pay with?</h3>
                        <p>bKash, Nagad, Rocket and bank transfer. The options appear at checkout.</p>

                        <h2>Returns</h2>

                        <h3>What if something arrives damaged or wrong?</h3>
                        <p>Tell us within 7 days with a photo and we will replace it, credit you or refund you. Hygiene items such as diapers and wipes have a 24-hour window. Full details are in our <a href="/return-policy">Return Policy</a>.</p>

                        <h3>How long does a refund take?</h3>
                        <p>Three to five working days after we approve the return, sent back the way you paid.</p>

                        <h2>Still stuck?</h2>
                        <p>Call or message the number in the footer. We answer during shop hours, and by the next working day otherwise.</p>
                        HTML,
                'content_bn' => <<<'HTML'
                        <h2>অর্ডার</h2>

                        <h3>অর্ডার করতে কি অ্যাকাউন্ট লাগে?</h3>
                        <p>না। শুধু নাম, নম্বর ও ঠিকানা দিয়েই গেস্ট হিসেবে অর্ডার করতে পারেন। অ্যাকাউন্ট থাকলে পরেরবার আর টাইপ করতে হয় না, আর আগের অর্ডারগুলো এক জায়গায় দেখা যায়।</p>

                        <h3>অর্ডার হয়েছে কিনা বুঝব কীভাবে?</h3>
                        <p>চেকআউটের পরপরই স্ক্রিনে অর্ডার নম্বর দেখাবে, আর আমরা এসএমএসেও পাঠাই। দুটোর একটাও না পেলে অর্ডারটি আমাদের কাছে পৌঁছায়নি — আবার চেষ্টা করুন বা ফোন করুন।</p>

                        <h3>অর্ডার বদলানো বা বাতিল করা যাবে?</h3>
                        <p>কুরিয়ারে দেওয়ার আগ পর্যন্ত যাবে। অর্ডার নম্বরসহ যত দ্রুত সম্ভব কল বা মেসেজ করুন।</p>

                        <h2>স্টক ও প্রি-অর্ডার</h2>

                        <h3>"প্রি-অর্ডার" লেখা মানে কী?</h3>
                        <p>পণ্যটি এখন স্টকে নেই, তবু আমরা অর্ডার নিচ্ছি। কত দিন অপেক্ষা করতে হতে পারে সহ শর্তগুলো কার্টে যোগ করার আগেই দেখানো হয়, এবং রাজি হলে তবেই এগোনো যায়।</p>

                        <h3>স্টক শেষ হওয়া পণ্য কবে আসবে?</h3>
                        <p>প্রি-অর্ডার বাটন থাকলে পণ্যের পাতার শর্তে সম্ভাব্য সময় লেখা থাকে। না থাকলে মেসেজ করুন, আমরা যতটুকু জানি জানাব।</p>

                        <h2>ডেলিভারি</h2>

                        <h3>ডেলিভারিতে কত সময় লাগে?</h3>
                        <p>শহরের ভেতরে সাধারণত ২৪–৪৮ ঘণ্টা। দেশের অন্য জায়গায় ৩–৫ দিন। বিস্তারিত আছে <a href="/shipping-policy">শিপিং পলিসি</a>-তে।</p>

                        <h3>ডেলিভারি চার্জ কত?</h3>
                        <p>অর্ডার নিশ্চিত করার আগেই চেকআউটে দেখানো হয়, আর ঠিকানা ঢাকার ভেতরে না বাইরে তার উপর নির্ভর করে। উপরের বারে লেখা পরিমাণের বেশি অর্ডারে ডেলিভারি ফ্রি।</p>

                        <h3>সারা বাংলাদেশে কি ডেলিভারি হয়?</h3>
                        <p>হ্যাঁ, কুরিয়ার পার্টনারের মাধ্যমে। দুর্গম কিছু এলাকায় একটু বেশি সময় লাগে।</p>

                        <h2>পেমেন্ট</h2>

                        <h3>পণ্য হাতে পেয়ে টাকা দেওয়া যাবে?</h3>
                        <p>হ্যাঁ — সব অর্ডারেই ক্যাশ অন ডেলিভারি আছে। রাইডারের জন্য সঠিক পরিমাণ টাকা প্রস্তুত রাখবেন।</p>

                        <h3>আর কীভাবে পেমেন্ট করা যায়?</h3>
                        <p>বিকাশ, নগদ, রকেট ও ব্যাংক ট্রান্সফার। অপশনগুলো চেকআউটে দেখাবে।</p>

                        <h2>রিটার্ন</h2>

                        <h3>পণ্য ভাঙা বা ভুল এলে কী হবে?</h3>
                        <p>৭ দিনের মধ্যে ছবিসহ জানালে আমরা বদলে দেব, স্টোর ক্রেডিট দেব বা টাকা ফেরত দেব। ডায়াপার-ওয়াইপসের মতো হাইজিন পণ্যের ক্ষেত্রে সময় ২৪ ঘণ্টা। বিস্তারিত <a href="/return-policy">রিটার্ন পলিসি</a>-তে।</p>

                        <h3>রিফান্ড পেতে কত দিন লাগে?</h3>
                        <p>রিটার্ন অনুমোদনের পর তিন থেকে পাঁচ কর্মদিবস, যেভাবে পেমেন্ট করেছিলেন সেভাবেই।</p>

                        <h2>এখনো সমাধান হয়নি?</h2>
                        <p>ফুটারের নম্বরে কল বা মেসেজ করুন। দোকান খোলা থাকলে সাথে সাথে, নাহলে পরের কর্মদিবসে উত্তর দেব।</p>
                        HTML,
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
