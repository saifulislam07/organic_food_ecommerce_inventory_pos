<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * A checkout someone started but did not finish: saved as soon as they type a
 * valid phone number, so the shop can call and ask what went wrong.
 */
class IncompleteOrder extends Model
{
    use Prunable;

    /** How long an unconverted lead is kept before the nightly prune drops it. */
    public const KEEP_DAYS = 90;

    /** The follow-up states, and the badge each one wears. */
    public const STATUSES = [
        'new' => ['New', 'danger'],
        'called' => ['Called', 'info'],
        'no_answer' => ['No Answer', 'warning'],
        'not_interested' => ['Not Interested', 'secondary'],
        'converted' => ['Converted', 'success'],
    ];

    /** States an admin can set by hand; converted only comes from a real order. */
    public const MANUAL_STATUSES = ['new', 'called', 'no_answer', 'not_interested'];

    protected $fillable = [
        'visitor_key', 'source', 'landing_page_id', 'user_id',
        'customer_name', 'customer_phone', 'customer_email', 'customer_address',
        'customer_area', 'delivery_type', 'pickup_point', 'notes',
        'items', 'subtotal', 'discount_amount', 'coupon_code', 'delivery_charge', 'total',
        'status', 'admin_note', 'handled_by', 'called_at', 'order_id',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'called_at' => 'datetime',
    ];

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', 'converted');
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst((string) $this->status);
    }

    public function getStatusColourAttribute(): string
    {
        return self::STATUSES[$this->status][1] ?? 'dark';
    }

    /** Converted rows are kept: they are the record of a sale the call saved. */
    public function prunable(): Builder
    {
        return static::open()->where('updated_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}
