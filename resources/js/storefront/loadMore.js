import http from '../shared/http';

/**
 * "Load more" on the shop page. The server returns another page of rendered
 * product cards, so anything appended has to be hydrated again — that is what
 * `afterAppend` is for.
 */
export function bindLoadMore(afterAppend = () => {}) {
    const button = document.querySelector('[data-load-more]');

    if (!button) return;

    const grid = document.getElementById('product-grid');
    const container = document.getElementById('load-more-container');
    const showingText = document.getElementById('showing-text');

    if (!grid) return;

    const idleLabel = button.innerHTML;
    const loadingLabel = button.dataset.loadingLabel || 'Loading…';
    const retryLabel = button.dataset.retryLabel || 'Try Again';

    button.addEventListener('click', async () => {
        const page = parseInt(button.dataset.page, 10) || 2;

        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingLabel}`;

        const params = new URLSearchParams(window.location.search);
        params.set('page', String(page));

        try {
            const { data } = await http.get(`${window.location.pathname}?${params.toString()}`);

            if (!data?.html?.trim()) {
                container?.remove();
                return;
            }

            grid.insertAdjacentHTML('beforeend', data.html);
            button.dataset.page = String(page + 1);

            if (data.showing && showingText) showingText.textContent = data.showing;

            afterAppend(grid);

            if (data.hasMore) {
                button.disabled = false;
                button.innerHTML = idleLabel;
            } else {
                container?.remove();
            }
        } catch (error) {
            console.error('[storefront] Load more failed', error);
            button.disabled = false;
            button.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${retryLabel}`;
        }
    });
}
