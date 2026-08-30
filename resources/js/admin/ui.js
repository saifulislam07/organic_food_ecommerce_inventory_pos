import { reactive } from 'vue';

/**
 * Toasts and the confirm dialog, shared by every admin page.
 *
 * Confirmations are opt-in through markup rather than per-page components: put
 * data-confirm="…" on a form and the themed dialog replaces the browser's own.
 */

let nextId = 0;

export const toasts = reactive([]);

export function toast(message, type = 'success', timeout = 4500) {
    const id = ++nextId;

    toasts.push({ id, message, type });

    if (timeout) {
        setTimeout(() => dismissToast(id), timeout);
    }

    return id;
}

export function dismissToast(id) {
    const index = toasts.findIndex((item) => item.id === id);

    if (index !== -1) toasts.splice(index, 1);
}

/* ----------------------------------------------------------------- confirm */

export const confirmState = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Delete',
    tone: 'danger',
    pending: null,
});

/** Ask, then run `onConfirm` if the answer is yes. */
export function askConfirm(options, onConfirm) {
    Object.assign(confirmState, {
        open: true,
        title: options.title || 'Are you sure?',
        message: options.message || '',
        confirmLabel: options.confirmLabel || 'Delete',
        tone: options.tone || 'danger',
        pending: onConfirm,
    });
}

export function resolveConfirm(accepted) {
    const action = confirmState.pending;

    confirmState.open = false;
    confirmState.pending = null;

    if (accepted && action) action();
}

/**
 * Intercepts any form carrying data-confirm. The form is submitted natively
 * afterwards, so nothing else about these pages has to change.
 */
export function interceptConfirmForms(root = document) {
    root.addEventListener(
        'submit',
        (event) => {
            const form = event.target.closest('form[data-confirm]');

            if (!form || form.dataset.confirmed === 'yes') return;

            event.preventDefault();

            askConfirm(
                {
                    title: form.dataset.confirmTitle || 'Are you sure?',
                    message: form.dataset.confirm,
                    confirmLabel: form.dataset.confirmLabel || 'Delete',
                    tone: form.dataset.confirmTone || 'danger',
                },
                () => {
                    form.dataset.confirmed = 'yes';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            );
        },
        true
    );
}

/**
 * Copy-to-clipboard buttons: put data-copy="…" on anything clickable.
 *
 * Campaign URLs are meant to be pasted into Facebook's ad composer, and
 * selecting one out of a table cell by hand is where typos come from.
 */
export function initCopyButtons(root = document) {
    root.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-copy]');

        if (!trigger) return;

        event.preventDefault();

        const value = trigger.dataset.copy;

        try {
            await navigator.clipboard.writeText(value);
            toast('লিংক কপি হয়েছে', 'success', 2500);
        } catch (error) {
            // Clipboard access needs HTTPS or localhost; show it to copy by hand.
            window.prompt('লিংকটি কপি করুন:', value);
        }
    });
}

/** Laravel's flash messages, handed over as JSON by the admin layout. */
export function flashToasts() {
    const el = document.getElementById('admin-flash');

    if (!el) return;

    try {
        const flash = JSON.parse(el.textContent);

        if (flash.success) toast(flash.success, 'success');
        if (flash.error) toast(flash.error, 'danger', 8000);

        (flash.errors || []).forEach((message) => toast(message, 'danger', 8000));
    } catch (error) {
        console.error('[admin] Could not read flash messages', error);
    }
}
