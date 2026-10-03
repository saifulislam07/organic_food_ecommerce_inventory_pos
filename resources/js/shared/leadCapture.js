/**
 * Saves a half-filled order form to the server while the customer types, so
 * the shop can call someone who gives up before pressing "order".
 *
 * Plain fetch rather than axios: landing pages load neither. It is best
 * effort and silent — a failed save must never get in the way of the form.
 */

const PHONE = /^(?:88)?01[3-9]\d{8}$/;

const latin = (value) => String(value ?? '').replace(/[০-৯]/g, (d) => '০১২৩৪৫৬৭৮৯'.indexOf(d));

/** True once the number is a full Bangladeshi mobile number. */
export function isCompletePhone(value) {
    return PHONE.test(latin(value).replace(/\D+/g, ''));
}

/**
 * @param {string} url       the capture endpoint
 * @param {() => FormData|Object} read  returns the current form values
 * @param {number} wait      quiet time before a save, in ms
 * @returns {{ schedule: () => void, flush: () => void, cancel: () => void }}
 */
export function createLeadCapture(url, read, wait = 1500) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;
    let lastSent = '';

    function send(keepalive = false) {
        clearTimeout(timer);
        timer = null;

        const data = read();
        const body = data instanceof FormData ? data : new FormData();

        if (!(data instanceof FormData)) {
            Object.entries(data).forEach(([key, value]) => body.append(key, value ?? ''));
        }

        if (!isCompletePhone(body.get('customer_phone'))) return;

        // Nothing new since the last save — skip the round trip.
        body.delete('_token');
        const fingerprint = new URLSearchParams(body).toString();

        if (fingerprint === lastSent) return;

        lastSent = fingerprint;

        fetch(url, {
            method: 'POST',
            body,
            keepalive,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
        }).catch(() => {
            // Let the next change try again.
            lastSent = '';
        });
    }

    // Leaving the page (closing the tab, going back) is exactly the moment
    // that matters, so send whatever is pending without waiting.
    const flushPending = () => {
        if (timer) send(true);
    };

    window.addEventListener('pagehide', flushPending);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flushPending();
    });

    return {
        schedule() {
            clearTimeout(timer);
            timer = setTimeout(() => send(), wait);
        },
        flush: () => send(),
        /** Call on submit, so a pending save cannot race the real order. */
        cancel() {
            clearTimeout(timer);
            timer = null;
        },
    };
}
