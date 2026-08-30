<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\PresentsVariantOptions;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\ProductVariant;
use App\Support\ImageStore;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminLandingPageController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug, PresentsVariantOptions, SearchesRecords;

    /** Where this screen's uploads live under public/uploads. */
    private const IMAGE_FOLDER = 'landing';

    /** Largest upload accepted, in kilobytes. Also shown on the form. */
    public const MAX_IMAGE_KB = 6144;

    public function index(Request $request)
    {
        $pages = $this->applySearch(
            LandingPage::query(),
            $request->input('search'),
            ['slug', 'internal_name', 'headline']
        )
            ->withCount('orders')
            // Cancelled orders were never money, so they do not count as sales.
            ->withSum(['orders as revenue' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.landing-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.landing-pages.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $data = $this->attributes($request, $validated);
        $data['slug'] = ($validated['slug'] ?? null) ?: $this->uniqueSlug($validated['internal_name'], 'landing_pages');
        $data['created_by'] = $request->user()?->id;

        foreach (['hero_image' => 'hero_image', 'og_image' => 'og_image'] as $field => $column) {
            if ($request->hasFile($field)) {
                $data[$column] = ImageStore::put($request->file($field), self::IMAGE_FOLDER);
            }
        }

        $page = DB::transaction(function () use ($data, $request, $validated) {
            $page = LandingPage::create($data);

            $this->syncItems($page, $validated['items'], $request);

            return $page;
        });

        return redirect()->route('admin.landing-pages.edit', $page)
            ->with('success', 'ল্যান্ডিং পেজ তৈরি হয়েছে। লিংক: '.$page->url());
    }

    public function edit(LandingPage $landingPage)
    {
        $landingPage->load('items.product', 'items.variant');

        return view('admin.landing-pages.edit', $this->formData($landingPage) + [
            'stats' => $this->stats($landingPage),
        ]);
    }

    /**
     * How this page has actually done, broken down by campaign.
     *
     * The campaign rows are what the utm tags on the ad links were for: the
     * page can be the same while one boost sells and another does not.
     */
    private function stats(LandingPage $page): array
    {
        // Cancelled orders were never money.
        $earned = "coalesce(sum(case when status != 'cancelled' then total else 0 end), 0) as revenue";

        $totals = $page->orders()
            ->selectRaw('count(*) as orders')
            ->selectRaw($earned)
            ->first();

        $campaigns = $page->orders()
            ->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign, count(*) as orders')
            ->selectRaw($earned)
            ->groupBy('utm_campaign')
            ->orderByDesc('orders')
            ->limit(10)
            ->get();

        $views = (int) $page->views;
        $orders = (int) ($totals->orders ?? 0);

        return [
            'views' => $views,
            'orders' => $orders,
            'revenue' => (float) ($totals->revenue ?? 0),
            'conversion' => $views > 0 ? round($orders / $views * 100, 1) : null,
            'campaigns' => $campaigns,
        ];
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $this->validated($request, $landingPage);

        $data = $this->attributes($request, $validated);
        $data['slug'] = $validated['slug'] ?? $landingPage->slug;

        foreach (['hero_image', 'og_image'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $previous = $landingPage->{$field};
            $data[$field] = ImageStore::put($request->file($field), self::IMAGE_FOLDER);

            if ($previous !== $data[$field]) {
                ImageStore::delete($previous);
            }
        }

        DB::transaction(function () use ($landingPage, $data, $validated, $request) {
            $landingPage->update($data);

            $this->syncItems($landingPage, $validated['items'], $request);
        });

        return redirect()->route('admin.landing-pages.edit', $landingPage)
            ->with('success', 'ল্যান্ডিং পেজ সংরক্ষণ করা হয়েছে।');
    }

    public function destroy(LandingPage $landingPage)
    {
        if ($reason = $this->blocksDeletion($landingPage)) {
            return back()->withErrors(['delete' => $reason]);
        }

        $landingPage->delete();

        return redirect()->route('admin.landing-pages.index')->with('success', 'ল্যান্ডিং পেজ মুছে ফেলা হয়েছে।');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, LandingPage::class, fn ($page) => $this->blocksDeletion($page));

        return $this->bulkResponse($result, 'landing pages', 'admin.landing-pages.index');
    }

    /**
     * A page that took orders is the only record of which campaign sold what.
     * Switching it off retires it without throwing that away.
     */
    private function blocksDeletion(LandingPage $page): ?string
    {
        if (! $page->orders()->exists()) {
            return null;
        }

        return '"'.$page->internal_name.'" থেকে অর্ডার এসেছে, তাই মুছে ফেলা যাবে না। বন্ধ করে দিন।';
    }

    /**
     * Copy a page so the next campaign starts from the last one.
     *
     * The copy is a draft on a new URL with its own counters — publishing it is
     * a separate, deliberate act. Pictures are copied rather than shared, or
     * deleting one page would blank the other.
     */
    public function duplicate(LandingPage $landingPage)
    {
        $landingPage->load('items');

        $copy = DB::transaction(function () use ($landingPage) {
            $copy = $landingPage->replicate(['views', 'created_by', 'created_at', 'updated_at']);

            $copy->slug = $this->uniqueSlug($landingPage->slug.'-copy', 'landing_pages');
            $copy->internal_name = $landingPage->internal_name.' (কপি)';
            $copy->is_active = false;
            $copy->views = 0;
            $copy->created_by = auth()->id();
            $copy->hero_image = ImageStore::duplicate($landingPage->hero_image, self::IMAGE_FOLDER);
            $copy->og_image = ImageStore::duplicate($landingPage->og_image, self::IMAGE_FOLDER);
            $copy->save();

            foreach ($landingPage->items as $item) {
                $row = $item->replicate(['created_at', 'updated_at']);
                $row->landing_page_id = $copy->id;
                $row->image = ImageStore::duplicate($item->image, self::IMAGE_FOLDER);
                $row->save();
            }

            return $copy;
        });

        return redirect()->route('admin.landing-pages.edit', $copy)
            ->with('success', 'কপি তৈরি হয়েছে — ড্রাফট অবস্থায় আছে, লাইভ করার আগে দেখে নিন।');
    }

    /* ------------------------------------------------------------ internals */

    private function formData(?LandingPage $page = null): array
    {
        return [
            'page' => $page,
            'variantOptions' => $this->variantOptions(),
            'itemRows' => $this->itemRows($page),
        ];
    }

    /** What the items repeater renders from, old input winning after a failure. */
    private function itemRows(?LandingPage $page): array
    {
        return old('items', $page
            ? $page->items->map(fn (LandingPageItem $item) => [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'label' => $item->label,
                'offer_price' => $item->offer_price,
                'compare_at_price' => $item->compare_at_price,
                'is_default' => $item->is_default,
                'min_qty' => $item->min_qty,
                'max_qty' => $item->max_qty,
                'image_url' => $item->imageUrl(),
            ])->values()->all()
            : []);
    }

    private function validated(Request $request, ?LandingPage $page = null): array
    {
        $validator = validator($request->all(), $this->rules($page), $this->messages());

        // Only checkable once the items are known: a bundle price above what
        // the parts come to would charge the customer less than it claims.
        $validator->after(function (Validator $validator) use ($request) {
            if ($request->input('selection_mode') !== LandingPage::MODE_BUNDLE) {
                return;
            }

            $bundle = $request->input('bundle_price');

            if (blank($bundle)) {
                return;
            }

            $parts = $this->partsTotal((array) $request->input('items', []));

            if ($parts > 0 && (float) $bundle > $parts) {
                $validator->errors()->add(
                    'bundle_price',
                    'কম্বো দাম আলাদা আলাদা দামের যোগফলের ('.number_format($parts).' টাকা) চেয়ে বেশি হতে পারবে না।'
                );
            }
        });

        return $validator->validate();
    }

    /** What the chosen items would cost bought separately. */
    private function partsTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $row) {
            $variant = filled($row['offer_price'] ?? null)
                ? null
                : ProductVariant::find($row['product_variant_id'] ?? 0);

            $price = filled($row['offer_price'] ?? null)
                ? (float) $row['offer_price']
                : (float) ($variant?->display_price ?? 0);

            $total += $price * max(1, (int) ($row['min_qty'] ?? 1));
        }

        return round($total, 2);
    }

    private function rules(?LandingPage $page): array
    {
        return [
            'internal_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('landing_pages', 'slug')->ignore($page?->id),
            ],
            'template' => ['nullable', Rule::in(array_keys(LandingPage::TEMPLATES))],

            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:255'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            // A hero banner off a camera or a designer is routinely 3-5 MB, and
            // ImageStore downscales and re-encodes it to WebP anyway, so the
            // stored file stays small whatever arrives here.
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            'video_url' => ['nullable', 'url', 'max:255'],
            'body' => ['nullable', 'string'],

            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'faqs' => ['nullable', 'array', 'max:20'],
            'faqs.*.q' => ['nullable', 'string', 'max:255'],
            'faqs.*.a' => ['nullable', 'string', 'max:2000'],
            'reviews' => ['nullable', 'array', 'max:20'],
            'reviews.*.name' => ['nullable', 'string', 'max:100'],
            'reviews.*.text' => ['nullable', 'string', 'max:1000'],
            'reviews.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sections' => ['nullable', 'array'],
            'sections.*' => [Rule::in(array_keys(LandingPage::BLOCKS))],

            'selection_mode' => ['required', Rule::in(array_keys(LandingPage::MODES))],
            'bundle_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_variant_id' => ['required', 'distinct', 'exists:product_variants,id'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.offer_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'items.*.compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'items.*.min_qty' => ['nullable', 'integer', 'min:1', 'max:100'],
            'items.*.max_qty' => ['nullable', 'integer', 'min:1', 'max:100'],
            'items.*.is_default' => ['nullable', 'boolean'],
            'item_images' => ['nullable', 'array'],
            'item_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],

            'delivery_mode' => ['required', Rule::in(['global', 'custom', 'free'])],
            'delivery_inside' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'delivery_outside' => ['nullable', 'numeric', 'min:0', 'max:99999'],

            'payment_mode' => ['required', Rule::in(['cod', 'advance'])],
            'advance_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'payment_note' => ['nullable', 'string', 'max:1000'],

            'form_fields' => ['nullable', 'array'],
            'form_fields.*' => [Rule::in(LandingPage::OPTIONAL_FIELDS)],
            'cta_text' => ['nullable', 'string', 'max:100'],

            'countdown_ends_at' => ['nullable', 'date'],
            'stock_note' => ['nullable', 'string', 'max:255'],

            'pixel_id' => ['nullable', 'string', 'max:32', 'regex:/^\d{10,20}$/'],
            'thankyou_headline' => ['nullable', 'string', 'max:255'],
            'thankyou_body' => ['nullable', 'string', 'max:2000'],

            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:180'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    private function messages(): array
    {
        return [
            'items.required' => 'অন্তত একটি প্রোডাক্ট যোগ করুন — নাহলে পেজে কেনার কিছু থাকবে না।',
            'items.*.product_variant_id.required' => 'প্রতিটি সারিতে একটি প্রোডাক্ট বেছে নিন।',
            'items.*.product_variant_id.distinct' => 'একই প্রোডাক্ট দুইবার যোগ করা যাবে না।',
            'slug.regex' => 'URL-এ শুধু ছোট হাতের অক্ষর, সংখ্যা ও হাইফেন চলবে।',
            'slug.unique' => 'এই URL অন্য একটি পেজ ব্যবহার করছে।',
            'pixel_id.regex' => 'Pixel ID শুধু সংখ্যা হয়, যেমন 123456789012345।',
            'ends_at.after' => 'শেষের তারিখ শুরুর তারিখের পরে হতে হবে।',
            'hero_image.max' => 'হিরো ছবিটি বেশি বড়। সর্বোচ্চ '.(self::MAX_IMAGE_KB / 1024).' MB পর্যন্ত চলবে।',
            'hero_image.image' => 'হিরো ছবির ফাইলটি ছবি হিসেবে পড়া যায়নি — JPG, PNG বা WebP দিন।',
            'og_image.max' => 'শেয়ার ছবিটি বেশি বড়। সর্বোচ্চ '.(self::MAX_IMAGE_KB / 1024).' MB পর্যন্ত চলবে।',
            'item_images.*.max' => 'প্রোডাক্টের ছবিটি বেশি বড়। সর্বোচ্চ '.(self::MAX_IMAGE_KB / 1024).' MB পর্যন্ত চলবে।',
        ];
    }

    /** Everything that goes straight onto the row, cleaned and normalised. */
    private function attributes(Request $request, array $validated): array
    {
        $data = collect($validated)
            ->except(['items', 'item_images', 'hero_image', 'og_image', 'slug', 'features', 'faqs', 'reviews', 'form_fields'])
            ->all();

        $data['body'] = RichText::clean($validated['body'] ?? null);
        $data['template'] = $validated['template'] ?? 'classic';

        // Repeaters post blank rows for anything the admin left alone.
        $data['features'] = array_values(array_filter(
            array_map('trim', $validated['features'] ?? []),
            fn ($line) => $line !== ''
        ));

        $data['faqs'] = array_values(array_filter(
            array_map(
                fn ($row) => ['q' => trim($row['q'] ?? ''), 'a' => trim($row['a'] ?? '')],
                $validated['faqs'] ?? []
            ),
            fn ($row) => $row['q'] !== ''
        ));

        $data['reviews'] = array_values(array_filter(
            array_map(fn ($row) => [
                'name' => trim($row['name'] ?? ''),
                'text' => trim($row['text'] ?? ''),
                'rating' => (int) ($row['rating'] ?? 5),
            ], $validated['reviews'] ?? []),
            fn ($row) => $row['text'] !== ''
        ));

        // Checkbox groups arrive as a list of what is on; store the shape each
        // is read back in.
        $data['sections'] = array_values($validated['sections'] ?? []);
        $data['form_fields'] = collect(LandingPage::OPTIONAL_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => in_array($field, $validated['form_fields'] ?? [], true)])
            ->all();

        $data['noindex'] = $request->boolean('noindex', true);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * Bring the page's items in line with what was posted.
     *
     * Rows keep their id where one came back, so an item that is only being
     * repriced does not lose the picture uploaded for it.
     */
    private function syncItems(LandingPage $page, array $rows, Request $request): void
    {
        $keptIds = [];
        $defaultTaken = false;

        foreach (array_values($rows) as $index => $row) {
            $variant = ProductVariant::find($row['product_variant_id']);

            if (! $variant) {
                continue;
            }

            // Exactly one item can be the pre-selected package.
            $isDefault = ! $defaultTaken && (bool) ($row['is_default'] ?? false);
            $defaultTaken = $defaultTaken || $isDefault;

            $minQty = max(1, (int) ($row['min_qty'] ?? 1));

            $attributes = [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'label' => filled($row['label'] ?? null) ? $row['label'] : null,
                'offer_price' => filled($row['offer_price'] ?? null) ? $row['offer_price'] : null,
                'compare_at_price' => filled($row['compare_at_price'] ?? null) ? $row['compare_at_price'] : null,
                'is_default' => $isDefault,
                'min_qty' => $minQty,
                'max_qty' => max($minQty, (int) ($row['max_qty'] ?? 10)),
                'sort_order' => $index,
            ];

            // An id from another page finds nothing here, so the worst a
            // tampered form can do is add a row.
            $id = (int) ($row['id'] ?? 0);
            $item = $id ? $page->items()->whereKey($id)->first() : null;

            if ($item) {
                $item->update($attributes);
            } else {
                $item = $page->items()->create($attributes);
            }

            $upload = $request->file("item_images.{$index}");

            if ($upload) {
                $previous = $item->image;
                $item->update(['image' => ImageStore::put($upload, self::IMAGE_FOLDER)]);
                ImageStore::delete($previous);
            }

            $keptIds[] = $item->id;
        }

        // Deleted one at a time so each row's hook removes its own picture.
        $page->items()->whereNotIn('id', $keptIds ?: [0])->get()->each->delete();

        // Nothing was marked, so the first package is the one that opens
        // selected — a radio group with no selection is a dead end.
        if (! $defaultTaken && $first = $page->items()->first()) {
            $first->update(['is_default' => true]);
        }
    }
}
