<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * Where an order came from.
     *
     * The three original values are how an order reached the system; the rest
     * are the channels a counter sale can arrive through, chosen by the cashier.
     * The admin order filter and the dashboard breakdown both read this list, so
     * a channel added here shows up in each without further work.
     */
    public const SOURCES = [
        'website' => 'Website',
        'pos' => 'Counter',
        'phone' => 'Phone Order',
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'landing' => 'Landing Page',
    ];

    /** The channels the POS screen offers; everything else is set by the system. */
    public const POS_SOURCES = ['pos', 'phone', 'facebook', 'whatsapp'];

    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_phone', 'customer_address',
        'customer_area', 'notes', 'subtotal', 'discount_amount', 'coupon_id', 'coupon_code',
        'delivery_charge', 'total', 'paid_amount',
        'status', 'payment_method', 'source', 'pickup_point',
        'landing_page_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'fbclid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /** True for a sale rung up at the counter, whichever channel it came in by. */
    public function isCounterSale(): bool
    {
        return in_array($this->source, self::POS_SOURCES, true);
    }

    /** What the cashier handed back, or null when no payment figure was taken. */
    public function getChangeDueAttribute(): ?float
    {
        if ($this->paid_amount === null) {
            return null;
        }

        return max(0, round((float) $this->paid_amount - (float) $this->total, 2));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'MH-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));
            }
        });
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'confirmed' => '<span class="badge bg-info">Confirmed</span>',
            'processing' => '<span class="badge bg-primary">Processing</span>',
            'shipped' => '<span class="badge bg-secondary">Shipped</span>',
            'delivered' => '<span class="badge bg-success">Delivered</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            default => '<span class="badge bg-dark">Unknown</span>',
        };
    }
}
