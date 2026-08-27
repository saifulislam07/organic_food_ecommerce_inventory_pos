<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * SMTP credentials live in the settings table so the shop owner can change them
 * without touching .env. This reads them and pushes them into the mail config.
 */
class MailSettings
{
    public const CACHE_KEY = 'mail_settings';

    /** key => [type, config path it feeds] */
    public const FIELDS = [
        'mail_host' => 'text',
        'mail_port' => 'text',
        'mail_username' => 'text',
        'mail_password' => Setting::TYPE_SECRET,
        'mail_encryption' => 'text',
        'mail_from_address' => 'text',
        'mail_from_name' => 'text',
    ];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        // Boot runs before migrations on a fresh install, and on every request
        // after that — so guard the table and cache the read.
        if (! self::tableIsReady()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function () {
            $values = [];

            foreach (array_keys(self::FIELDS) as $key) {
                $values[$key] = Setting::get($key);
            }

            return $values;
        });
    }

    public static function save(array $values): void
    {
        foreach (self::FIELDS as $key => $type) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            // An empty password field means "leave the stored one alone".
            if ($type === Setting::TYPE_SECRET && blank($values[$key])) {
                continue;
            }

            Setting::put($key, $values[$key], $type);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** True once a host has been configured, i.e. the shop can actually send mail. */
    public static function isConfigured(): bool
    {
        return filled(self::all()['mail_host'] ?? null);
    }

    /** Push stored values over the config that ships in config/mail.php. */
    public static function apply(): void
    {
        $stored = self::all();

        if (blank($stored['mail_host'] ?? null)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $stored['mail_host'],
            'mail.mailers.smtp.port' => (int) ($stored['mail_port'] ?: 587),
            'mail.mailers.smtp.username' => $stored['mail_username'] ?: null,
            'mail.mailers.smtp.password' => $stored['mail_password'] ?: null,
        ]);

        // Laravel 11+ calls this "scheme"; empty means plain SMTP.
        $encryption = $stored['mail_encryption'] ?? null;
        config(['mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : null]);

        if (filled($stored['mail_from_address'] ?? null)) {
            config(['mail.from.address' => $stored['mail_from_address']]);
        }

        if (filled($stored['mail_from_name'] ?? null)) {
            config(['mail.from.name' => $stored['mail_from_name']]);
        }
    }

    private static function tableIsReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
