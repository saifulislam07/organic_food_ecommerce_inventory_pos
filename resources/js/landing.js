/**
 * Campaign landing pages.
 *
 * Its own entry point rather than a slice of storefront.js: these pages carry
 * paid traffic on mobile connections and load no Vue, no cart and no shop
 * chrome. Everything here is decoration — the running total and one pixel
 * event. The server recalculates every figure before an order is written,
 * so nothing below can change what a customer is charged.
 */

import { createLeadCapture } from './shared/leadCapture';

// The page is read in Bengali, so its figures are written in Bengali numerals —
// the same ones App\Support\Bangla prints on the server.
const bengali = (value) => String(value).replace(/\d/g, (digit) => '০১২৩৪৫৬৭৮৯'[digit]);

const money = (value) => '৳' + bengali(Math.round(value).toLocaleString('en-US'));

function readConfig() {
    const el = document.getElementById('lp-config');

    if (!el) return null;

    try {
        return JSON.parse(el.textContent);
    } catch (error) {
        console.error('[landing] Could not read the page config', error);
        return null;
    }
}

/* ------------------------------------------------------------------ totals */

function bindTotals(form, config) {
    const areaSelect = form.querySelector('[data-area]');
    const goodsOut = form.querySelector('[data-total-goods]');
    const deliveryOut = form.querySelector('[data-total-delivery]');
    // One of these lives in the form, another in the sticky bar.
    const grandOuts = document.querySelectorAll('[data-total-grand]');

    // The headline price sits in the hero band, above and outside the form.
    const priceNow = document.querySelector('[data-price-now]');
    const priceWas = document.querySelector('[data-price-was]');
    const priceSave = document.querySelector('[data-price-save]');

    function goodsTotal() {
        if (config.mode === 'bundle') {
            return Number(config.bundleTotal) || 0;
        }

        if (config.mode === 'multi') {
            return [...form.querySelectorAll('[data-pick] [data-qty]')].reduce(
                (sum, input) => sum + Number(input.dataset.price || 0) * Number(input.value || 0),
                0
            );
        }

        const chosen = form.querySelector('input[name="item_id"]:checked');
        const quantity = Number(form.querySelector('#lp-qty')?.value || 1);

        return chosen ? Number(chosen.dataset.price || 0) * quantity : 0;
    }

    function deliveryFor(goods) {
        const rule = config.delivery || {};
        const side = areaSelect?.value === 'dhaka_outside' ? 'outside' : 'inside';
        const freeOver = Number(rule.freeOver?.[side] || 0);

        if (freeOver > 0 && goods >= freeOver) return 0;

        return Number(rule[side] || 0);
    }

    /** Keep the headline price in step with the package that is selected. */
    function syncUnitPrice() {
        const chosen = form.querySelector('input[name="item_id"]:checked');

        if (!chosen || !priceNow) return;

        const price = Number(chosen.dataset.price || 0);
        const compare = Number(chosen.dataset.compare || 0);

        priceNow.textContent = money(price);

        if (priceWas) {
            const worth = compare > price;

            priceWas.hidden = !worth;
            priceWas.textContent = money(compare);

            if (priceSave) {
                priceSave.hidden = !worth;
                priceSave.textContent = money(compare - price) + ' সাশ্রয়';
            }
        }
    }

    function redraw() {
        const goods = goodsTotal();
        const delivery = deliveryFor(goods);

        syncUnitPrice();

        if (goodsOut) goodsOut.textContent = money(goods);
        if (deliveryOut) deliveryOut.textContent = delivery > 0 ? money(delivery) : 'ফ্রি';

        grandOuts.forEach((el) => {
            el.textContent = money(goods + delivery);
        });
    }

    form.addEventListener('change', redraw);
    redraw();
}

/* ------------------------------------------------------------- pickers */

/**
 * "এটা নিতে চাই", then − n +, for each product on a several-items page.
 *
 * The number box under the buttons is the real field and posts as it is; this
 * only moves its value. Below the product's minimum a step goes to 0 rather
 * than to a quantity the server would round back up.
 */
function bindPickers(form) {
    const pickers = form.querySelectorAll('[data-pick]');

    if (!pickers.length) return;

    document.documentElement.classList.add('lp-js');

    const summary = form.querySelector('[data-pick-summary]');

    function summarise() {
        if (!summary) return;

        let count = 0;
        let total = 0;

        form.querySelectorAll('[data-pick] [data-qty]').forEach((input) => {
            const qty = Number(input.value || 0);

            count += qty;
            total += qty * Number(input.dataset.price || 0);
        });

        summary.classList.toggle('is-empty', count === 0);
        summary.textContent = count
            ? `${bengali(count)}টি পণ্য বেছে নিয়েছেন — ${money(total)}`
            : 'এখনো কোনো পণ্য বেছে নেননি';
    }

    pickers.forEach((pick) => {
        const box = pick.querySelector('[data-stepper]');

        if (!box) return;

        const input = box.querySelector('[data-qty]');
        const inc = box.querySelector('[data-inc]');
        const min = Math.max(1, Number(box.dataset.min) || 1);
        const max = Math.max(min, Number(box.dataset.max) || min);

        const clamp = (value) => (value <= 0 ? 0 : Math.min(max, Math.max(min, value)));

        function show(value) {
            input.value = value;
            pick.classList.toggle('is-picked', value > 0);
            inc.disabled = value >= max;
        }

        // Buttons announce the change so the totals redraw; typing into the box
        // fires its own change, which reaches the form after this tidies it.
        function set(value) {
            show(clamp(value));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        box.querySelector('[data-add]').addEventListener('click', () => {
            set(min);
            inc.focus();
        });
        inc.addEventListener('click', () => set(Number(input.value || 0) + 1));
        box.querySelector('[data-dec]').addEventListener('click', () => {
            const next = Number(input.value || 0) - 1;

            set(next < min ? 0 : next);

            if (Number(input.value) === 0) box.querySelector('[data-add]').focus();
        });
        input.addEventListener('change', () => show(clamp(Math.floor(Number(input.value) || 0))));

        show(clamp(Number(input.value || 0)));
    });

    form.addEventListener('change', summarise);
}

/* ------------------------------------------------------------------ pixel */

/**
 * InitiateCheckout, once, the first time someone actually starts filling the
 * form. Firing it on page load would make every passer-by look like a lead.
 */
function bindCheckoutEvent(form) {
    let fired = false;

    const fire = () => {
        if (fired || typeof window.fbq !== 'function') return;

        fired = true;
        window.fbq('track', 'InitiateCheckout');
    };

    form.addEventListener('input', fire, { once: false });
}

/* ---------------------------------------------------------- lead capture */

/**
 * Saves the form while it is filled in, so a visitor who leaves without
 * ordering can still be called. The whole form posts, as the order would —
 * the server works out the products and prices from it.
 */
function bindLeadCapture(form) {
    const url = form.dataset.captureUrl;

    if (!url) return null;

    const capture = createLeadCapture(url, () => new FormData(form));

    form.addEventListener('input', () => capture.schedule());
    form.addEventListener('change', () => capture.schedule());

    return capture;
}

/* ------------------------------------------------------------------- boot */

function boot() {
    const form = document.getElementById('lp-order');

    if (!form) return;

    bindPickers(form);

    const config = readConfig();

    if (config) bindTotals(form, config);

    bindCheckoutEvent(form);

    const capture = bindLeadCapture(form);

    // Double submits are how one customer becomes two orders.
    form.addEventListener('submit', () => {
        capture?.cancel();

        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.textContent = 'পাঠানো হচ্ছে…';
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
