<?php

namespace App\Support;

/**
 * Editor output is stored as HTML and printed unescaped, so it is cleaned once
 * on the way in rather than on every render.
 *
 * The real control is who can reach the admin panel at all — this is the second
 * lock, for the day a staff account is taken over or someone pastes markup from
 * a page they did not write.
 */
class RichText
{
    /** Tags that have no business in a description or a policy page. */
    private const BANNED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base'];

    public static function clean(?string $html): ?string
    {
        if (blank($html)) {
            return $html === null ? null : '';
        }

        $tags = implode('|', self::BANNED_TAGS);

        // Paired tags go with their contents; a bare <script> would otherwise
        // leave its body behind as text.
        $html = preg_replace("#<({$tags})\b[^>]*>.*?</\\1\s*>#is", '', $html);
        $html = preg_replace("#<\/?({$tags})\b[^>]*>#i", '', $html);

        // onclick=, onerror= and friends, quoted or not.
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*[^\s>]+#i', '', $html);

        // javascript: and data: urls in href/src.
        $html = preg_replace('#\s(href|src)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\2#i', '', $html);

        return trim($html);
    }

    /**
     * Renders a stored value for the storefront.
     *
     * Text written before the editor arrived is plain, with real newlines, so
     * it still has to be escaped and turned into <br>. Anything the editor
     * produced is already HTML and was cleaned when it was saved.
     */
    public static function display(?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        return $value === strip_tags($value)
            ? nl2br(e($value))
            : self::clean($value);
    }

    /**
     * Cleans the rich fields of a validated payload in place.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public static function cleanKeys(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::clean($data[$key]);
            }
        }

        return $data;
    }
}
