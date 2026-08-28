<?php

namespace App\Support;

use App\Sms\SmsManager;

/**
 * wa.me only accepts a number in international form. The shop's number is
 * typed by hand in Site Settings — usually as 01716-952365 — so every link
 * has to be normalised before it is rendered.
 */
class Whatsapp
{
    /** Link to the shop's own WhatsApp, or null when no number is configured. */
    public static function shopUrl(?string $text = null): ?string
    {
        return self::url(ChatSettings::whatsappNumber(), $text);
    }

    /** Link to any number, e.g. a customer's mobile from an order. */
    public static function url(?string $number, ?string $text = null): ?string
    {
        $normalised = SmsManager::normalise($number);

        if ($normalised === null) {
            return null;
        }

        $url = 'https://wa.me/'.$normalised;

        return $text ? $url.'?text='.rawurlencode($text) : $url;
    }
}
