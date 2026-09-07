<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money an investor took back out.
 *
 * Allowed to exceed what they put in: an owner drawing this month's profit is
 * the ordinary case, not a mistake. The form says so when it happens rather
 * than refusing to save.
 */
class Withdrawal extends Model
{
    protected $fillable = ['investor_id', 'amount', 'withdrawn_at', 'paid_from', 'notes'];

    protected $casts = [
        'withdrawn_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
