<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Interfaces\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'area',
        'address',
        'is_default',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
