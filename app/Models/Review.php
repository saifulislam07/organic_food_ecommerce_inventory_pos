<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A star rating and comment on one product.
 *
 * Two ways in: a customer writes one from a delivered order (user_id and
 * order_id set, is_approved false until an admin reviews it), or an admin
 * types one in directly (no user/order behind it, approved immediately).
 * Either way this table is the only source the storefront reads from.
 */
class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_id',
        'customer_name', 'rating', 'title', 'body', 'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /** Filled and empty stars as one string, for a plain-text render. */
    public function getStarsAttribute(): string
    {
        $filled = max(0, min(5, $this->rating));

        return str_repeat('★', $filled).str_repeat('☆', 5 - $filled);
    }
}
