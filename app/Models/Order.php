<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_phone', 'customer_address',
        'customer_area', 'notes', 'subtotal', 'discount_amount', 'delivery_charge', 'total',
        'status', 'payment_method', 'source', 'pickup_point'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'MH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            }
        });
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
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
