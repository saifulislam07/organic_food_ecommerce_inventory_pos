<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value_en', 'value_bn', 'type'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;

        $locale = app()->getLocale();
        return $setting->{"value_{$locale}"} ?? $setting->value_en ?? $default;
    }
}
