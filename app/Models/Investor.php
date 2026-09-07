<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Someone who put money into the shop: an owner, a partner, a relative.
 *
 * A balance is never stored. It is what they put in less what they took out,
 * asked of the two ledgers every time — a kept total is a number that drifts
 * the first time a row is edited or deleted.
 */
class Investor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * The three numbers, from whatever is loaded.
     *
     * Reads the withCount sums when the list query supplied them and falls back
     * to the relations otherwise, so one accessor serves both the table and a
     * single record without the table running a query per row.
     */
    public function totalInvested(): float
    {
        return $this->summed('investments_sum_amount', fn () => $this->investments()->sum('amount'));
    }

    public function totalWithdrawn(): float
    {
        return $this->summed('withdrawals_sum_amount', fn () => $this->withdrawals()->sum('amount'));
    }

    /**
     * withSum's column when the query asked for it, a query when it did not.
     *
     * Tested with array_key_exists rather than ??, because withSum returns null
     * for an investor with no rows and that null must still count as answered —
     * otherwise the list falls back to a query for exactly the investors whose
     * answer is zero.
     */
    private function summed(string $attribute, callable $fallback): float
    {
        return (float) (array_key_exists($attribute, $this->attributes)
            ? $this->attributes[$attribute]
            : $fallback());
    }

    /**
     * What the shop still holds of theirs.
     *
     * Can legitimately go negative: an owner drawing profit has taken out more
     * than they put in, which is normal and not an error.
     */
    public function balance(): float
    {
        return $this->totalInvested() - $this->totalWithdrawn();
    }

    /** Everything that moved, newest first, both ledgers in one list. */
    public function statement(): Collection
    {
        $in = $this->investments->map(fn (Investment $row) => [
            'type' => 'investment',
            'date' => $row->invested_at,
            'amount' => (float) $row->amount,
            'account' => $row->received_in,
            'notes' => $row->notes,
            'id' => $row->id,
        ]);

        $out = $this->withdrawals->map(fn (Withdrawal $row) => [
            'type' => 'withdrawal',
            'date' => $row->withdrawn_at,
            'amount' => (float) $row->amount,
            'account' => $row->paid_from,
            'notes' => $row->notes,
            'id' => $row->id,
        ]);

        return $in->concat($out)
            // Two movements on one date sort by what was entered last, so a
            // correction entered afterwards reads as the later line.
            ->sortByDesc(fn ($row) => [$row['date']->timestamp, $row['id']])
            ->values();
    }
}
