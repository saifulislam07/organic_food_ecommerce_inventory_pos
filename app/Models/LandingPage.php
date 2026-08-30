<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One campaign, one URL, one job: take the order.
 *
 * A landing page borrows the shop's products but not its catalogue prices, its
 * layout, or its navigation — everything a visitor arriving from an ad could
 * click instead of buying is deliberately absent.
 */
class LandingPage extends Model
{
    use CleansUpImages;

    /** Optional blocks, in the order they are offered in the admin. */
    public const BLOCKS = [
        'video' => 'ভিডিও',
        'features' => 'কেন কিনবেন',
        'body' => 'বিস্তারিত বর্ণনা',
        'gallery' => 'ছবির গ্যালারি',
        'reviews' => 'কাস্টমার রিভিউ',
        'delivery' => 'ডেলিভারি ও পেমেন্ট',
        'faqs' => 'সাধারণ প্রশ্ন',
    ];

    /** A page that was never configured shows everything it has content for. */
    public const DEFAULT_SECTIONS = ['video', 'features', 'body', 'gallery', 'reviews', 'delivery', 'faqs'];

    /** Name and phone are always asked for; these are the ones that can go. */
    public const OPTIONAL_FIELDS = ['address', 'area', 'note', 'email'];

    public const DEFAULT_FORM_FIELDS = ['address' => true, 'area' => true, 'note' => true, 'email' => false];

    /** How the items on the page are offered to the visitor. */
    public const MODE_SINGLE = 'single';

    public const MODE_MULTI = 'multi';

    public const MODE_BUNDLE = 'bundle';

    public const MODES = [
        self::MODE_SINGLE => 'একটি প্যাকেজ বেছে নেবে',
        self::MODE_MULTI => 'একাধিক আইটেম, আলাদা পরিমাণ',
        self::MODE_BUNDLE => 'সব একসাথে, ফিক্সড কম্বো দাম',
    ];

    public const TEMPLATES = ['classic' => 'Classic'];

    protected $fillable = [
        'slug', 'internal_name', 'template',
        'headline', 'subheadline', 'badge_text', 'hero_image', 'video_url', 'body',
        'features', 'faqs', 'reviews', 'sections',
        'selection_mode', 'bundle_price',
        'delivery_mode', 'delivery_inside', 'delivery_outside',
        'payment_mode', 'advance_amount', 'payment_note',
        'form_fields', 'cta_text',
        'countdown_ends_at', 'stock_note',
        'pixel_id', 'thankyou_headline', 'thankyou_body',
        'meta_title', 'meta_description', 'og_image', 'noindex',
        'is_active', 'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'features' => 'array',
        'faqs' => 'array',
        'reviews' => 'array',
        'sections' => 'array',
        'form_fields' => 'array',
        'bundle_price' => 'decimal:2',
        'delivery_inside' => 'decimal:2',
        'delivery_outside' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'noindex' => 'boolean',
        'is_active' => 'boolean',
        'countdown_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $page) {
            self::deleteUploadedImage($page->hero_image, 'landing/');
            self::deleteUploadedImage($page->og_image, 'landing/');
        });
    }

    /* ------------------------------------------------------------ relations */

    public function items(): HasMany
    {
        return $this->hasMany(LandingPageItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* --------------------------------------------------------------- state */

    /** Live right now: switched on, started, and not yet expired. */
    public function isRunning(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        return ! ($this->ends_at && $this->ends_at->isPast());
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Why the page is not taking orders, in the visitor's language. */
    public function closedReason(): ?string
    {
        if ($this->is_active && $this->starts_at?->isFuture()) {
            return 'এই অফারটি এখনো শুরু হয়নি।';
        }

        if ($this->is_active && $this->ends_at?->isPast()) {
            return 'এই অফারের মেয়াদ শেষ হয়ে গেছে।';
        }

        return $this->is_active ? null : 'এই অফারটি এখন বন্ধ আছে।';
    }

    /* ----------------------------------------------------------------- urls */

    public function url(): string
    {
        return route('landing.show', $this->slug);
    }

    /**
     * The link to paste into the ad. The campaign tags come back on the order,
     * which is the only way to tell later which boost paid for itself.
     */
    public function adUrl(): string
    {
        return $this->url().'?'.http_build_query([
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => Str::slug($this->internal_name) ?: $this->slug,
        ]);
    }

    public function heroImageUrl(): ?string
    {
        return blank($this->hero_image) ? null : ImageStore::url($this->hero_image);
    }

    public function ogImageUrl(): ?string
    {
        return blank($this->og_image) ? $this->heroImageUrl() : ImageStore::url($this->og_image);
    }

    /* -------------------------------------------------------------- content */

    /** Enabled blocks, in the order they should render. */
    public function enabledSections(): array
    {
        $sections = $this->sections;

        if (! is_array($sections)) {
            return self::DEFAULT_SECTIONS;
        }

        return array_values(array_intersect($sections, array_keys(self::BLOCKS)));
    }

    public function showsSection(string $key): bool
    {
        return in_array($key, $this->enabledSections(), true);
    }

    /** Whether one of the optional form fields is asked for. */
    public function asksFor(string $field): bool
    {
        $fields = is_array($this->form_fields) ? $this->form_fields : [];

        return (bool) ($fields[$field] ?? self::DEFAULT_FORM_FIELDS[$field] ?? false);
    }

    /** @return array<int, string> */
    public function featureList(): array
    {
        return array_values(array_filter(
            is_array($this->features) ? $this->features : [],
            fn ($line) => filled($line)
        ));
    }

    /** @return array<int, array{q: string, a: string}> */
    public function faqList(): array
    {
        return array_values(array_filter(
            is_array($this->faqs) ? $this->faqs : [],
            fn ($row) => is_array($row) && filled($row['q'] ?? null)
        ));
    }

    /** @return array<int, array{name: string, text: string, rating: int}> */
    public function reviewList(): array
    {
        return array_values(array_filter(
            is_array($this->reviews) ? $this->reviews : [],
            fn ($row) => is_array($row) && filled($row['text'] ?? null)
        ));
    }

    public function ctaText(): string
    {
        return filled($this->cta_text) ? $this->cta_text : 'অর্ডার করুন';
    }

    /**
     * The video as something an <iframe> can load.
     *
     * The admin pastes whatever the address bar showed, which for YouTube is
     * one of three shapes and never the embed one.
     */
    public function videoEmbedUrl(): ?string
    {
        $url = trim((string) $this->video_url);

        if ($url === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.watch')) {
            return 'https://www.facebook.com/plugins/video.php?href='.urlencode($url).'&show_text=false';
        }

        // Anything else is assumed to already be embeddable.
        return $url;
    }

    /** Every picture behind the items on this page, for the gallery block. */
    public function galleryImages(): array
    {
        return $this->items
            ->flatMap(fn (LandingPageItem $item) => $item->product
                ? $item->product->images->map(fn ($image) => $image->url)
                : collect())
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    /* -------------------------------------------------------------- pricing */

    public function isBundle(): bool
    {
        return $this->selection_mode === self::MODE_BUNDLE;
    }

    public function isMulti(): bool
    {
        return $this->selection_mode === self::MODE_MULTI;
    }

    /**
     * What the whole bundle costs. Falls back to the sum of its parts when no
     * bundle price was set, so a half-filled page still quotes something sane.
     */
    public function bundleTotal(): float
    {
        if ($this->bundle_price !== null) {
            return (float) $this->bundle_price;
        }

        return (float) $this->items->sum(fn (LandingPageItem $item) => $item->price() * $item->min_qty);
    }

    /** What the parts would cost separately — the struck-through number. */
    public function bundleCompareTotal(): ?float
    {
        $total = (float) $this->items->sum(
            fn (LandingPageItem $item) => ($item->comparePrice() ?? $item->price()) * $item->min_qty
        );

        return $total > $this->bundleTotal() ? $total : null;
    }

    /**
     * Delivery for this page: free, this page's own numbers, or the shop's.
     *
     * An empty custom box falls through to the shop's charge rather than
     * quoting zero by accident.
     */
    public function deliveryChargeFor(?string $area, float $subtotal): float
    {
        if ($this->delivery_mode === 'free') {
            return 0.0;
        }

        $outside = $area === 'dhaka_outside';

        if ($this->delivery_mode === 'custom') {
            $charge = $outside ? $this->delivery_outside : $this->delivery_inside;

            if ($charge !== null) {
                return (float) $charge;
            }
        }

        $threshold = (float) Setting::get('free_delivery_threshold', 2000);

        if ($threshold > 0 && $subtotal >= $threshold) {
            return 0.0;
        }

        return $outside
            ? (float) Setting::get('shipping_fee_outside', 120)
            : (float) Setting::get('shipping_fee_inside', 60);
    }

    /** The pixel this page reports to: its own, or the shop's. */
    public function pixelId(): ?string
    {
        $id = filled($this->pixel_id) ? $this->pixel_id : Setting::get('seo_facebook_pixel');

        return blank($id) ? null : trim($id);
    }
}
