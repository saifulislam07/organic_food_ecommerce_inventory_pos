/**
 * Campaign landing pages.
 *
 * Its own entry point rather than a slice of storefront.js: these pages carry
 * paid traffic on mobile connections and load no Vue, no cart and no shop
 * chrome. Everything here is decoration — the running total, a countdown, one
 * pixel event. The server recalculates every figure before an order is written,
 * so nothing below can change what a customer is charged.
 */

const money = (value) => '৳' + Math.round(value).toLocaleString('en-US');

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
            return [...form.querySelectorAll('select[data-qty]')].reduce(
                (sum, select) => sum + Number(select.dataset.price || 0) * Number(select.value || 0),
                0
            );
        }

        const chosen = form.querySelector('input[name="item_id"]:checked');
        const quantity = Number(form.querySelector('#lp-qty')?.value || 1);

        return chosen ? Number(chosen.dataset.price || 0) * quantity : 0;
    }

    function deliveryFor(goods) {
        const rule = config.delivery || {};

        if (rule.freeOver > 0 && goods >= rule.freeOver) return 0;

        return areaSelect?.value === 'dhaka_outside' ? Number(rule.outside || 0) : Number(rule.inside || 0);
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

/* --------------------------------------------------------------- countdown */

function bindCountdown() {
    const box = document.querySelector('[data-countdown]');

    if (!box) return;

    const target = new Date(box.dataset.countdown).getTime();
    const parts = {
        d: box.querySelector('[data-cd="d"]'),
        h: box.querySelector('[data-cd="h"]'),
        m: box.querySelector('[data-cd="m"]'),
        s: box.querySelector('[data-cd="s"]'),
    };

    // The offer's own numbers are in Bengali, so its clock should be too.
    const bengali = (value) =>
        String(value).padStart(2, '0').replace(/\d/g, (digit) => '০১২৩৪৫৬৭৮৯'[digit]);

    function tick() {
        const left = target - Date.now();

        if (left <= 0) {
            box.remove();
            clearInterval(timer);
            return;
        }

        const seconds = Math.floor(left / 1000);

        parts.d.textContent = bengali(Math.floor(seconds / 86400));
        parts.h.textContent = bengali(Math.floor(seconds / 3600) % 24);
        parts.m.textContent = bengali(Math.floor(seconds / 60) % 60);
        parts.s.textContent = bengali(seconds % 60);
    }

    const timer = setInterval(tick, 1000);
    tick();
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

/* ------------------------------------------------------------------- boot */

function boot() {
    const form = document.getElementById('lp-order');

    bindCountdown();

    if (!form) return;

    const config = readConfig();

    if (config) bindTotals(form, config);

    bindCheckoutEvent(form);

    // Double submits are how one customer becomes two orders.
    form.addEventListener('submit', () => {
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
