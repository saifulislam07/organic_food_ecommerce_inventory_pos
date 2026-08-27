/**
 * Runtime config the storefront needs from Laravel — route URLs, locale and the
 * few translated strings the JS itself produces. Rendered by
 * layouts/frontend.blade.php as <script type="application/json" id="storefront-config">.
 */
const FALLBACK = {
    routes: {},
    locale: 'en',
    freeDeliveryThreshold: 0,
    whatsapp: '',
    strings: {},
};

function read() {
    const el = document.getElementById('storefront-config');

    if (!el) {
        console.warn('[storefront] Missing #storefront-config — cart actions are disabled.');
        return FALLBACK;
    }

    try {
        return { ...FALLBACK, ...JSON.parse(el.textContent) };
    } catch (error) {
        console.error('[storefront] Could not parse #storefront-config', error);
        return FALLBACK;
    }
}

export const config = read();

export function route(name) {
    return config.routes[name] ?? null;
}

/** Translated string with an English fallback baked into the call site. */
export function t(key, fallback = '') {
    return config.strings[key] ?? fallback;
}
