/**
 * Tick rows, delete them together.
 *
 * Driven by markup so no table has to become a Vue component:
 *
 *   <form id="bulk-products" data-bulk data-bulk-noun="products">
 *     <button data-bulk-submit>                          in the action bar
 *   </form>
 *   <input type="checkbox" data-bulk-all form="bulk-products">        header
 *   <input type="checkbox" name="ids[]" value="7" form="bulk-products">  row
 *
 * The checkboxes join the form by id rather than sitting inside it: a table row
 * carries its own delete form, and a form nested in another form is dropped by
 * the parser — the row's button would then submit the bulk form instead.
 */
export function initBulkDelete(root = document) {
    root.querySelectorAll('form[data-bulk]:not([data-bulk-ready])').forEach((form) => {
        form.dataset.bulkReady = 'yes';

        // Only form.elements sees fields attached from outside the form.
        const fields = () => Array.from(form.elements);
        const boxes = () => fields().filter((el) => el.name === 'ids[]' && !el.disabled);
        const selected = () => boxes().filter((box) => box.checked);

        const all = fields().find((el) => el.hasAttribute('data-bulk-all'));
        const bar = form.querySelector('[data-bulk-bar]');
        const count = form.querySelector('[data-bulk-count]');
        const noun = form.dataset.bulkNoun || 'rows';
        const one = noun.replace(/s$/, '');

        function sync() {
            const chosen = selected().length;
            const total = boxes().length;
            const label = `${chosen} ${chosen === 1 ? one : noun}`;

            if (bar) bar.classList.toggle('d-none', chosen === 0);
            if (count) count.textContent = `${label} selected`;

            if (all) {
                all.checked = total > 0 && chosen === total;
                all.indeterminate = chosen > 0 && chosen < total;
            }

            // The confirm dialog should say how many are going.
            form.dataset.confirm = `Delete ${label}? This cannot be undone.`;
        }

        if (all) {
            all.addEventListener('change', () => {
                boxes().forEach((box) => { box.checked = all.checked; });
                sync();
            });
        }

        boxes().forEach((box) => box.addEventListener('change', sync));

        // Nothing ticked means nothing to ask about.
        form.addEventListener('submit', (event) => {
            if (selected().length === 0) event.preventDefault();
        }, true);

        sync();
    });
}
