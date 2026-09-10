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

    /** The states an order can be in, and the badge each one wears. */
    public const STATUSES = [
        'pending' => ['Pending', 'warning'],
        'confirmed' => ['Confirmed', 'info'],
        'processing' => ['Processing', 'primary'],
        'shipped' => ['Shipped', 'secondary'],
        'delivered' => ['Delivered', 'success'],
        'cancelled' => ['Cancelled', 'danger'],
    ];

    /** Nothing more will happen to an order in one of these. */
    public const CLOSED = ['delivered', 'cancelled'];

    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_phone', 'customer_address',
        'customer_area', 'notes', 'subtotal', 'discount_amount', 'coupon_id', 'coupon_code',
        'delivery_charge', 'total', 'paid_amount',
        'status', 'payment_method', 'source', 'pickup_point',
        'landing_page_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'fbclid',
        'collected_amount', 'collected_in', 'courier_charge', 'settlement_note', 'delivered_at',
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
        'collected_amount' => 'decimal:2',
        'courier_charge' => 'decimal:2',
        'delivered_at' => 'datetime',
        'courier_synced_at' => 'datetime',
    ];

    /** True for a sale rung up at the counter, whichever channel it came in by. */
    public function isCounterSale(): bool
    {
        return in_array($this->source, self::POS_SOURCES, true);
    }

    /** True once nothing further is expected to happen to it. */
    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED, true);
    }

    /** What the cashier handed back, or null when no payment figure was taken. */
    public function getChangeDueAttribute(): ?float
    {
        if ($this->paid_amount === null) {
            return null;
        }

        return max(0, round((float) $this->paid_amount - (float) $this->total, 2));
    }

    /**
     * What is still owed on this order — what a courier has to collect.
     *
     * Capped below at zero so an overpaid counter sale, where paid_amount
     * includes the note the customer handed over, does not come out negative
     * and ask a courier to hand money back.
     */
    public function getAmountDueAttribute(): float
    {
        return max(0, round((float) $this->total - (float) ($this->paid_amount ?? 0), 2));
    }

    /** True once someone has recorded what the delivery actually settled for. */
    public function isSettled(): bool
    {
        return $this->collected_amount !== null;
    }

    /**
     * The money this order really brought in.
     *
     * The settlement when there is one, and the order total when there is not —
     * an order nobody has settled has to be worth something to the books, and
     * its face value is the only honest guess. The report says which it used.
     */
    public function getRealisedAmountAttribute(): float
    {
        return $this->isSettled()
            ? (float) $this->collected_amount
            : (float) $this->total;
    }

    /** Which account head the money landed in, settled or assumed. */
    public function getRealisedAccountAttribute(): ?string
    {
        return $this->isSettled() ? $this->collected_in : $this->payment_method;
    }

    /**
     * What the shop expected the courier to hand over: the outstanding balance
     * less the courier's own fee. The settlement form starts from this figure.
     */
    public function getExpectedCollectionAttribute(): float
    {
        return round($this->amount_due - (float) $this->courier_charge, 2);
    }

    /** How far the settlement missed what was expected, negative when short. */
    public function getSettlementVarianceAttribute(): ?float
    {
        return $this->isSettled()
            ? round((float) $this->collected_amount - $this->expected_collection, 2)
            : null;
    }

    /** True when this parcel is with a courier we can ask about. */
    public function hasCourier(): bool
    {
        return filled($this->courier);
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
        [$label, $colour] = self::STATUSES[$this->status] ?? ['Unknown', 'dark'];

        return '<span class="badge bg-'.$colour.'">'.e($label).'</span>';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst((string) $this->status);
    }

    public function getStatusColourAttribute(): string
    {
        return self::STATUSES[$this->status][1] ?? 'dark';
    }
}
