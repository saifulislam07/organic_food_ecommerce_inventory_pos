<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Order;
use App\Services\LandingPageOrder;
use App\Support\CampaignTracking;
use App\Support\OrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Takes an order straight off a landing page — no cart, no account, no
 * checkout. One POST, and the customer is on the thank-you page.
 */
class LandingOrderController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $page = LandingPage::with(['items.product', 'items.variant.comboItems.component'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $page->isRunning()) {
            return back()->withInput()->withErrors([
                'order' => $page->closedReason() ?? 'এই অফারটি এখন বন্ধ আছে।',
            ]);
        }

        // A hidden box no human sees, so anything in it came from a script.
        // Answered as an ordinary failure rather than naming the trap.
        if (filled($request->input('website'))) {
            return back()->withInput()->withErrors([
                'order' => 'অর্ডারটি গ্রহণ করা যায়নি, আবার চেষ্টা করুন।',
            ]);
        }

        // Numbers get typed as 01712-345678 or +8801712345678; validate the
        // digits rather than the punctuation.
        $request->merge([
            'customer_phone' => preg_replace('/\D+/', '', (string) $request->input('customer_phone')),
        ]);

        $validated = $request->validate($this->rules($page), $this->messages());

        $service = app(LandingPageOrder::class);
        $lines = $service->lines($page, $request->all());

        if (empty($lines)) {
            return back()->withInput()->withErrors(['order' => 'অন্তত একটি প্রোডাক্ট নির্বাচন করুন।']);
        }

        try {
            $order = $service->place($page, $lines, $validated, CampaignTracking::capture($request));
        } catch (RuntimeException $e) {
            // Sold out between opening the page and submitting it.
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }

        // Same notifications a website order sends — a landing order is not a
        // lesser kind of order.
        app(OrderNotifier::class)->placed($order->fresh('items'));

        return redirect()->route('landing.thankyou', [$page->slug, $order->order_number]);
    }

    public function thankYou(string $slug, string $orderNumber)
    {
        $page = LandingPage::where('slug', $slug)->firstOrFail();

        $order = Order::where('order_number', $orderNumber)
            ->where('landing_page_id', $page->getKey())
            ->with('items')
            ->firstOrFail();

        return view('landing.thank-you', compact('page', 'order'));
    }

    /** Which boxes this particular page decided to ask for. */
    private function rules(LandingPage $page): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^(?:88)?01[3-9]\d{8}$/'],
            'customer_address' => [$page->asksFor('address') ? 'required' : 'nullable', 'string', 'max:1000'],
            'customer_area' => [
                $page->asksFor('area') ? 'required' : 'nullable',
                Rule::in(['dhaka_inside', 'dhaka_outside']),
            ],
            'email' => [$page->asksFor('email') ? 'required' : 'nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** The page is Bengali, so the complaints are too. */
    private function messages(): array
    {
        return [
            'customer_name.required' => 'আপনার নাম লিখুন।',
            'customer_phone.required' => 'মোবাইল নম্বর লিখুন।',
            'customer_phone.regex' => 'সঠিক মোবাইল নম্বর লিখুন, যেমন ০১৭১২৩৪৫৬৭৮।',
            'customer_address.required' => 'ডেলিভারির ঠিকানা লিখুন।',
            'customer_area.required' => 'ডেলিভারি এলাকা বেছে নিন।',
            'customer_area.in' => 'ডেলিভারি এলাকা বেছে নিন।',
            'email.required' => 'ইমেইল ঠিকানা লিখুন।',
            'email.email' => 'সঠিক ইমেইল ঠিকানা লিখুন।',
        ];
    }
}
