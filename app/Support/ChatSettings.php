<?php

namespace App\Support;

use App\Models\Setting;
use App\Sms\SmsManager;

/**
 * The two chat buttons that float over the storefront.
 *
 * Both are off until someone fills them in, so a fresh install shows nothing
 * rather than a button that opens an empty conversation.
 */
class ChatSettings
{
    public const FIELDS = [
        'chat_whatsapp_enabled',
        'chat_whatsapp_number',
        'chat_whatsapp_message_en',
        'chat_whatsapp_message_bn',
        'chat_messenger_enabled',
        'chat_messenger_id',
        'chat_position',
    ];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        $values = [];

        foreach (self::FIELDS as $key) {
            $values[$key] = Setting::get($key);
        }

        return $values;
    }

    public static function get(string $key, $default = null)
    {
        $value = self::all()[$key] ?? null;

        return blank($value) ? $default : $value;
    }

    public static function save(array $values): void
    {
        foreach (self::FIELDS as $key) {
            if (array_key_exists($key, $values)) {
                Setting::put($key, $values[$key]);
            }
        }

        self::forget();
    }

    public static function forget(): void
    {
        Setting::flush();
    }

    /**
     * The WhatsApp number to chat with: the one set here, or the shop's
     * general contact number when this page was never filled in.
     */
    public static function whatsappNumber(): ?string
    {
        return self::get('chat_whatsapp_number') ?: Setting::get('whatsapp');
    }

    /** The greeting the customer's message box is pre-filled with. */
    public static function whatsappMessage(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        return self::get("chat_whatsapp_message_{$locale}")
            ?: self::get('chat_whatsapp_message_en');
    }

    /** wa.me link for the floating button, or null when it should not show. */
    public static function whatsappUrl(): ?string
    {
        if (! self::enabled('chat_whatsapp_enabled')) {
            return null;
        }

        return Whatsapp::url(self::whatsappNumber(), self::whatsappMessage());
    }

    /**
     * m.me link for the floating button.
     *
     * The admin may paste a full URL, a page username or a numeric page id —
     * all three end up as m.me/<handle>.
     */
    public static function messengerUrl(): ?string
    {
        if (! self::enabled('chat_messenger_enabled')) {
            return null;
        }

        $handle = self::messengerHandle();

        return $handle ? 'https://m.me/'.$handle : null;
    }

    public static function messengerHandle(): ?string
    {
        $value = trim((string) self::get('chat_messenger_id'));

        if ($value === '') {
            return null;
        }

        // Accept anything the admin might paste from the address bar.
        $value = preg_replace('#^https?://#i', '', $value);
        $value = preg_replace('#^(www\.)?(m\.me|messenger\.com|facebook\.com|fb\.com)/#i', '', $value);
        $value = ltrim($value, '/');
        $value = strtok($value, '?');

        return trim($value, '/') ?: null;
    }

    /** Which side of the screen the buttons sit on. */
    public static function position(): string
    {
        return self::get('chat_position') === 'left' ? 'left' : 'right';
    }

    /** True when at least one button will render. */
    public static function anyEnabled(): bool
    {
        return self::whatsappUrl() !== null || self::messengerUrl() !== null;
    }

    /**
     * A toggle that was never saved counts as on for WhatsApp, because the
     * shop had a floating WhatsApp button before this page existed.
     */
    private static function enabled(string $key): bool
    {
        $value = self::all()[$key] ?? null;

        if ($value === null) {
            return $key === 'chat_whatsapp_enabled'
                && SmsManager::normalise(Setting::get('whatsapp')) !== null;
        }

        return (bool) $value;
    }
}
