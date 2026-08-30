<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Services\InventoryService;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing a landing page sells.
 *
 * The label, picture and price can all be overridden per page — the same 3kg
 * pack can be "ঈদ স্পেশাল ৩ কেজি" at one price on one campaign and something
 * else on the next — and anything left blank falls back to the catalogue.
 */
class LandingPageItem extends Model
{
    use CleansUpImages;

    protected $fillable = [
        'landing_page_id', 'product_id', 'product_variant_id',
        'label', 'offer_price', 'compare_at_price', 'image',
        'is_default', 'min_qty', 'max_qty', 'sort_order',
    ];

    protected $casts = [
        'offer_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_default' => 'boolean',
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(fn (self $item) => self::deleteUploadedImage($item->image, 'landing/'));
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** What the customer pays for one of these. Never read from the request. */
    public function price(): float
    {
        if ($this->offer_price !== null) {
            return (float) $this->offer_price;
        }

        return (float) ($this->variant?->display_price ?? 0);
    }

    /** The struck-through number, when there is a saving worth showing. */
    public function comparePrice(): ?float
    {
        $compare = $this->compare_at_price !== null
            ? (float) $this->compare_at_price
            : (float) ($this->variant?->price ?? 0);

        return $compare > $this->price() ? $compare : null;
    }

    public function savings(): float
    {
        $compare = $this->comparePrice();

        return $compare === null ? 0.0 : $compare - $this->price();
    }

    public function label(): string
    {
        if (filled($this->label)) {
            return $this->label;
        }

        $product = $this->product?->name ?? 'প্রোডাক্ট';
        $variant = $this->variant?->name;

        return $variant ? "{$product} — {$variant}" : $product;
    }

    /**
     * The picture for this row, or null when there genuinely is not one.
     *
     * Product::image_url substitutes a placeholder graphic, which is right for
     * a catalogue grid and wrong here: a column of identical grey squares
     * beside the packages looks broken. No picture, no <img>.
     */
    public function imageUrl(): ?string
    {
        if (filled($this->image)) {
            return ImageStore::url($this->image);
        }

        return filled($this->product?->image) ? $this->product->image_url : null;
    }

    /** How many can still be sold, components of a combo taken into account. */
    public function availableStock(): int
    {
        return $this->variant ? app(InventoryService::class)->available($this->variant) : 0;
    }

    public function inStock(): bool
    {
        return $this->availableStock() >= max(1, $this->min_qty);
    }

    /** The quantities a visitor may pick, clamped to what is on the shelf. */
    public function quantityRange(): array
    {
        $max = min(max($this->max_qty, $this->min_qty), max($this->availableStock(), 0));

        if ($max < $this->min_qty) {
            return [];
        }

        return range(max(1, $this->min_qty), $max);
    }
}
