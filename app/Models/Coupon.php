<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A discount code.
 *
 * The rule that matters is per product, not per order: a coupon and a product's
 * own offer never stack. Whichever takes more off a single unit wins, and the
 * other is discarded — so a 10% coupon on something already reduced by 25%
 * changes nothing, while a 40% coupon replaces that 25% outright.
 *
 * discountPerUnit() is the whole of that rule; CartService applies it and
 * decides which side won.
 */
class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'Percentage (%)',
        self::TYPE_FIXED => 'Fixed amount (৳)',
    ];

    /** Which products the code touches. */
    public const SCOPES = [
        'all' => 'Every product',
        'categories' => 'Selected categories',
        'products' => 'Selected products',
    ];

    protected $fillable = [
        'code', 'label_en', 'label_bn', 'type', 'value',
        'max_discount', 'min_order_amount', 'applies_to',
        'usage_limit', 'usage_limit_per_user', 'used_count',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /** Codes are matched case-insensitively, so they are stored one way. */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();
        $value = $this->{"label_{$locale}"} ?? null;

        return filled($value) ? $value : ($this->label_en ?: $this->code);
    }

    /* ------------------------------------------------------------ the rule */

    /**
     * What this coupon takes off one unit of something listed at $listPrice.
     *
     * Never more than the price itself: a ৳200 code against a ৳150 jar makes it
     * free, not worth ৳-50.
     */
    public function discountPerUnit(float $listPrice): float
    {
        if ($listPrice <= 0) {
            return 0.0;
        }

        $cut = $this->type === self::TYPE_PERCENT
            ? $listPrice * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->type === self::TYPE_PERCENT && $this->max_discount !== null) {
            $cut = min($cut, (float) $this->max_discount);
        }

        return round(min($cut, $listPrice), 2);
    }

    /** Whether the code covers this product at all. */
    public function coversProduct(?Product $product): bool
    {
        if (! $product) {
            return false;
        }

        return match ($this->applies_to) {
            'categories' => $this->categories->contains('id', $product->category_id),
            'products' => $this->products->contains('id', $product->id),
            default => true,
        };
    }

    /* ------------------------------------------------------ redeemability */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Case-insensitive lookup with the scope lists already loaded. */
    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return static::with(['categories:id', 'products:id'])->where('code', $code)->first();
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

    public function hasExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Why this code cannot be used right now, or null when it can.
     *
     * Returns a translation key rather than a sentence: the cart and the
     * checkout both report it, in whichever language the shopper is reading.
     */
    public function rejectionReason(?User $user, float $subtotal): ?string
    {
        if (! $this->is_active || ! $this->hasStarted()) {
            return 'inactive';
        }

        if ($this->hasExpired()) {
            return 'expired';
        }

        if ($this->isExhausted()) {
            return 'exhausted';
        }

        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            return 'min_order';
        }

        if ($user && $this->usage_limit_per_user !== null
            && $this->orders()->where('user_id', $user->id)->count() >= $this->usage_limit_per_user) {
            return 'per_user';
        }

        return null;
    }
}
