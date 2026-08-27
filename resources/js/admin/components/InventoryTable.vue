<script setup>
import { computed, reactive, ref } from 'vue';
import http, { errorMessage } from '../../shared/http';
import { money } from '../../shared/format';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    lowStockThreshold: { type: Number, default: 5 },
});

/**
 * Local copy of the server rendered page: `draft` is what sits in the input,
 * `stock` is what the server last confirmed.
 */
const items = reactive(
    props.rows.map((row) => ({
        ...row,
        draft: row.stock,
        saving: false,
        saved: false,
        error: null,
    }))
);

const filter = ref('');

const visible = computed(() => {
    const needle = filter.value.trim().toLowerCase();

    if (!needle) return items;

    return items.filter(
        (item) =>
            item.product_name.toLowerCase().includes(needle) ||
            (item.variant_name || '').toLowerCase().includes(needle) ||
            (item.sku || '').toLowerCase().includes(needle)
    );
});

function isDirty(item) {
    return Number(item.draft) !== Number(item.stock);
}

function nudge(item, delta) {
    item.draft = Math.max(0, Number(item.draft || 0) + delta);
    item.saved = false;
}

async function save(item) {
    const stock = Math.floor(Number(item.draft));

    if (!Number.isFinite(stock) || stock < 0) {
        item.error = 'Stock must be 0 or more.';
        return;
    }

    item.saving = true;
    item.error = null;

    try {
        const { data } = await http.patch(item.update_url, { stock });

        item.stock = data.new_stock ?? stock;
        item.draft = item.stock;
        item.saved = true;

        setTimeout(() => {
            item.saved = false;
        }, 1500);
    } catch (error) {
        item.error = errorMessage(error, 'Could not update stock.');
    } finally {
        item.saving = false;
    }
}
</script>

<template>
    <div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <h5 class="mb-0">Product Stock Levels</h5>
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-funnel"></i></span>
                    <input v-model="filter" type="search" class="form-control bg-light border-0" placeholder="Filter this page…">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Product &amp; Variant</th>
                                <th>Current Stock</th>
                                <th>Price</th>
                                <th class="text-end pe-4">Quick Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!visible.length">
                                <td colspan="4" class="text-center text-muted py-5">
                                    No matching products on this page.
                                </td>
                            </tr>

                            <tr v-for="item in visible" :key="item.id" :class="{ 'table-success': item.saved }">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img :src="item.image" :alt="item.product_name" class="rounded shadow-sm" style="width:40px;height:40px;object-fit:cover;">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ item.product_name }}</h6>
                                            <small class="text-muted">
                                                {{ item.variant_name }}
                                                <span v-if="item.sku"> · SKU: {{ item.sku }}</span>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge fs-6 px-3 py-2"
                                        :class="item.stock < lowStockThreshold ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'"
                                    >{{ item.stock }} in stock</span>
                                </td>
                                <td>{{ money(item.price) }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex flex-column align-items-end gap-1">
                                        <div class="d-inline-flex gap-2 align-items-center">
                                            <div class="input-group input-group-sm" style="width:132px;">
                                                <button type="button" class="btn btn-outline-secondary" :disabled="item.saving" @click="nudge(item, -1)">-</button>
                                                <input
                                                    v-model.number="item.draft"
                                                    type="number"
                                                    min="0"
                                                    class="form-control text-center"
                                                    :disabled="item.saving"
                                                    @keydown.enter="save(item)"
                                                >
                                                <button type="button" class="btn btn-outline-secondary" :disabled="item.saving" @click="nudge(item, 1)">+</button>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                :disabled="item.saving || !isDirty(item)"
                                                @click="save(item)"
                                            >
                                                <span v-if="item.saving" class="spinner-border spinner-border-sm"></span>
                                                <span v-else>Update</span>
                                            </button>
                                        </div>
                                        <small v-if="item.error" class="text-danger">{{ item.error }}</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
