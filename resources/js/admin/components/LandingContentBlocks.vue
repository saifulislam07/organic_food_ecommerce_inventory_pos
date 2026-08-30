<script setup>
import { computed, ref } from 'vue';

/**
 * The parts of a landing page that repeat: selling points, reviews, questions —
 * plus which blocks appear and in what order.
 *
 * A block that is switched on but has nothing in it still renders nothing, so
 * the order list is about arrangement rather than a second set of switches.
 */
const props = defineProps({
    blocks: { type: Object, default: () => ({}) },
    sections: { type: Array, default: () => [] },
    features: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
});

const allKeys = Object.keys(props.blocks);

/**
 * Every block in the order it should render: the ones already arranged first,
 * then anything new that the saved order predates.
 */
const order = ref([
    ...props.sections.filter((key) => allKeys.includes(key)),
    ...allKeys.filter((key) => !props.sections.includes(key)),
]);

/*
 * Straight from the prop: the Blade already substitutes the defaults for a page
 * that was never configured, so an empty list here means the admin genuinely
 * switched every block off — filling it back in would undo their work.
 */
const enabled = ref(new Set(props.sections));

const activeOrder = computed(() => order.value.filter((key) => enabled.value.has(key)));

function toggle(key) {
    const next = new Set(enabled.value);

    next.has(key) ? next.delete(key) : next.add(key);
    enabled.value = next;
}

function move(index, direction) {
    const target = index + direction;

    if (target < 0 || target >= order.value.length) return;

    const [key] = order.value.splice(index, 1);
    order.value.splice(target, 0, key);
}

/* ------------------------------------------------------------- repeaters */

const featureRows = ref(props.features.length ? [...props.features] : ['']);
const faqRows = ref(props.faqs.length ? props.faqs.map((row) => ({ ...row })) : [{ q: '', a: '' }]);
const reviewRows = ref(
    props.reviews.length ? props.reviews.map((row) => ({ ...row })) : [{ name: '', text: '', rating: 5 }]
);

/*
 * One pair of functions per repeater rather than a generic add(list, blank):
 * a ref handed to a function *from the template* arrives unwrapped, so the
 * generic version was pushing onto `undefined.value` and doing nothing at all.
 * Closing over each ref here keeps `.value` real.
 */

function addFeature() {
    featureRows.value.push('');
}

function removeFeature(index) {
    featureRows.value.splice(index, 1);

    // The last row is kept so there is always somewhere to type.
    if (!featureRows.value.length) addFeature();
}

function addReview() {
    reviewRows.value.push({ name: '', text: '', rating: 5 });
}

function removeReview(index) {
    reviewRows.value.splice(index, 1);

    if (!reviewRows.value.length) addReview();
}

function addFaq() {
    faqRows.value.push({ q: '', a: '' });
}

function removeFaq(index) {
    faqRows.value.splice(index, 1);

    if (!faqRows.value.length) addFaq();
}
</script>

<template>
    <div>
        <!-- Which blocks, in which order -->
        <div class="card bg-light border-0 p-3 mb-4">
            <h6 class="fw-bold mb-1">পেজের ব্লক ও ক্রম</h6>
            <p class="text-muted small mb-3">
                টিক তুলে দিলে ব্লকটি পেজে থাকবে না। তীর দিয়ে উপরে-নিচে সাজান।
            </p>

            <input v-for="key in activeOrder" :key="`section-${key}`" type="hidden" name="sections[]" :value="key">

            <div v-for="(key, index) in order" :key="key"
                 class="d-flex align-items-center gap-2 bg-white rounded border px-3 py-2 mb-2">
                <input type="checkbox" class="form-check-input mt-0" :checked="enabled.has(key)" @change="toggle(key)">
                <span :class="enabled.has(key) ? 'fw-bold' : 'text-muted text-decoration-line-through'">
                    {{ blocks[key] }}
                </span>
                <div class="btn-group btn-group-sm ms-auto">
                    <button type="button" class="btn btn-outline-secondary" :disabled="index === 0" @click="move(index, -1)">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :disabled="index === order.length - 1" @click="move(index, 1)">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Selling points -->
        <div class="mb-4">
            <label class="form-label fw-bold">কেন কিনবেন — বুলেট পয়েন্ট</label>
            <div v-for="(line, index) in featureRows" :key="`feature-${index}`" class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-check2-circle text-success"></i></span>
                <input v-model="featureRows[index]" type="text" name="features[]" class="form-control"
                       maxlength="255" placeholder="যেমন: ১০০% খাঁটি, কোনো ভেজাল নেই">
                <button type="button" class="btn btn-outline-danger" @click="removeFeature(index)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success" @click="addFeature()">
                <i class="bi bi-plus-circle"></i> পয়েন্ট যোগ করুন
            </button>
        </div>

        <!-- Reviews -->
        <div class="mb-4">
            <label class="form-label fw-bold">কাস্টমার রিভিউ</label>
            <div v-for="(row, index) in reviewRows" :key="`review-${index}`" class="row g-2 align-items-start mb-2">
                <div class="col-md-3">
                    <input v-model="row.name" type="text" :name="`reviews[${index}][name]`" class="form-control"
                           maxlength="100" placeholder="নাম">
                </div>
                <div class="col-md-6">
                    <input v-model="row.text" type="text" :name="`reviews[${index}][text]`" class="form-control"
                           maxlength="1000" placeholder="কী বলেছেন">
                </div>
                <div class="col-md-2">
                    <select v-model.number="row.rating" :name="`reviews[${index}][rating]`" class="form-select">
                        <option v-for="star in 5" :key="star" :value="star">{{ star }} ★</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger w-100"
                            @click="removeReview(index)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success"
                    @click="addReview()">
                <i class="bi bi-plus-circle"></i> রিভিউ যোগ করুন
            </button>
        </div>

        <!-- FAQ -->
        <div>
            <label class="form-label fw-bold">সাধারণ প্রশ্ন ও উত্তর</label>
            <div v-for="(row, index) in faqRows" :key="`faq-${index}`" class="card bg-light border-0 p-2 mb-2">
                <div class="d-flex gap-2 mb-2">
                    <input v-model="row.q" type="text" :name="`faqs[${index}][q]`" class="form-control"
                           maxlength="255" placeholder="প্রশ্ন">
                    <button type="button" class="btn btn-outline-danger"
                            @click="removeFaq(index)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <textarea v-model="row.a" :name="`faqs[${index}][a]`" class="form-control" rows="2"
                          maxlength="2000" placeholder="উত্তর"></textarea>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success" @click="addFaq()">
                <i class="bi bi-plus-circle"></i> প্রশ্ন যোগ করুন
            </button>
        </div>
    </div>
</template>
