<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money an investor put into the shop on a given day.
 *
 * Not an expense and not revenue: it turns somebody's money into the shop's
 * cash, which is a balance-sheet move. The profit and loss figure ignores it;
 * the account table below that figure does not.
 */
class Investment extends Model
{
    protected $fillable = ['investor_id', 'amount', 'invested_at', 'received_in', 'notes'];

    protected $casts = [
        'invested_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
