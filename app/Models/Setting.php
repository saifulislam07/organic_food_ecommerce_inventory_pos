<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value_en', 'value_bn', 'type'];

    /** Values stored with this type are encrypted at rest. */
    public const TYPE_SECRET = 'secret';

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        $locale = app()->getLocale();
        $value = $setting->{"value_{$locale}"} ?? $setting->value_en ?? $default;

        if ($setting->type === self::TYPE_SECRET && filled($value)) {
            return self::decrypt($value) ?? $default;
        }

        return $value;
    }

    /** Store a value, encrypting it when the type says so. */
    public static function put(string $key, $value, string $type = 'text'): self
    {
        if ($type === self::TYPE_SECRET && filled($value)) {
            $value = Crypt::encryptString((string) $value);
        }

        return self::updateOrCreate(['key' => $key], [
            'value_en' => $value,
            'value_bn' => $value,
            'type' => $type,
        ]);
    }

    /**
     * A stored secret can predate the current APP_KEY, or have been written as
     * plain text by hand — neither should take the whole page down.
     */
    private static function decrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
