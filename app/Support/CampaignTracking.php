<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The tags that say which ad brought a visitor.
 *
 * They arrive in the query string, ride along the order form as hidden inputs,
 * and end up on the order — which is the only way to answer "did that boost pay
 * for itself?" once the money has been spent.
 */
class CampaignTracking
{
    public const FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'fbclid'];

    /** Column width in the orders table; anything longer is somebody's mistake. */
    private const MAX_LENGTH = 255;

    /**
     * Reads the tags from wherever they are on this request — the query string
     * on the landing page itself, hidden fields when the form posts back.
     *
     * @return array<string, string|null>
     */
    public static function capture(Request $request): array
    {
        $values = [];

        foreach (self::FIELDS as $field) {
            $value = $request->input($field);

            $values[$field] = is_string($value) && trim($value) !== ''
                ? mb_substr(trim($value), 0, self::MAX_LENGTH)
                : null;
        }

        return $values;
    }
}
