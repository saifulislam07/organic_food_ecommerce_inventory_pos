<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Site settings are read constantly — the layout alone asks for twenty of them
 * on every page — so the table is read once and held, rather than queried per
 * key. Any write empties the cache.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value_en', 'value_bn', 'type'];

    /** Values stored with this type are encrypted at rest. */
    public const TYPE_SECRET = 'secret';

    public const CACHE_KEY = 'settings.rows';

    /** Per-request copy, so even a cache hit costs nothing the second time. */
    private static ?array $rows = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::flush());
        static::deleted(fn () => self::flush());
    }

    public static function get($key, $default = null)
    {
        $row = self::rows()[$key] ?? null;

        if (! $row) {
            return $default;
        }

        $locale = app()->getLocale();
        $value = $row["value_{$locale}"] ?? $row['value_en'] ?? $default;

        if ($row['type'] === self::TYPE_SECRET && filled($value)) {
            return self::decrypt($value) ?? $default;
        }

        return $value;
    }

    /**
     * The stored value for one key in one language, ignoring the current
     * locale — what a bilingual admin form needs to fill both its boxes.
     */
    public static function value(string $key, string $locale = 'en', $default = null)
    {
        $row = self::rows()[$key] ?? null;

        if (! $row) {
            return $default;
        }

        $value = $row["value_{$locale}"] ?? $row['value_en'];

        if ($row['type'] === self::TYPE_SECRET && filled($value)) {
            return self::decrypt($value) ?? $default;
        }

        return $value ?? $default;
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

    /** Drops both copies. Called for you whenever a setting is written. */
    public static function flush(): void
    {
        self::$rows = null;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Every row, keyed by setting key.
     *
     * The table is missing during the very first migration, and the cache
     * store itself may be a table that does not exist yet — neither should
     * take a page down, so both fall back to "no settings".
     *
     * @return array<string, array{value_en: ?string, value_bn: ?string, type: ?string}>
     */
    private static function rows(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        try {
            return self::$rows = Cache::rememberForever(self::CACHE_KEY, fn () => self::read());
        } catch (\Throwable) {
            // The cache store is a table too, and it may not be there yet.
            // Losing the cache must not mean losing the settings.
        }

        try {
            return self::$rows = self::read();
        } catch (\Throwable) {
            return self::$rows = [];
        }
    }

    /** @return array<string, array{value_en: ?string, value_bn: ?string, type: ?string}> */
    private static function read(): array
    {
        return self::query()
            ->get(['key', 'value_en', 'value_bn', 'type'])
            ->keyBy('key')
            ->map(fn (self $setting) => [
                'value_en' => $setting->value_en,
                'value_bn' => $setting->value_bn,
                'type' => $setting->type,
            ])
            ->all();
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
