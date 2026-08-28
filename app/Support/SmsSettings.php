<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Gateway credentials live in the settings table so the shop owner can change
 * provider without a deploy. Mirrors MailSettings.
 */
class SmsSettings
{
    /** key => storage type */
    public const FIELDS = [
        'sms_driver' => 'text',
        'sms_sender_id' => 'text',
        'sms_api_key' => Setting::TYPE_SECRET,
        'sms_endpoint' => 'text',
    ];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        $values = [];

        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = Setting::get($key);
        }

        $values['sms_driver'] = $values['sms_driver'] ?: config('sms.default');

        return $values;
    }

    public static function save(array $values): void
    {
        foreach (self::FIELDS as $key => $type) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            // Blank secret means "keep what is stored".
            if ($type === Setting::TYPE_SECRET && blank($values[$key])) {
                continue;
            }

            Setting::put($key, $values[$key], $type);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Setting::flush();
    }

    /** True when a real gateway is selected and has the credentials it needs. */
    public static function isConfigured(): bool
    {
        $settings = self::all();

        if (($settings['sms_driver'] ?? 'log') === 'log') {
            return false;
        }

        return filled($settings['sms_api_key'] ?? null) && filled($settings['sms_sender_id'] ?? null);
    }
}
