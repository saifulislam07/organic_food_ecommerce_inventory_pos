<script setup>
import { computed, ref, watch } from 'vue';
import { money } from '../../shared/format';

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    freeDeliveryThreshold: { type: Number, default: 0 },
    feeInside: { type: Number, default: 0 },
    feeOutside: { type: Number, default: 0 },
    pickupPoints: { type: Array, default: () => [] },
    savedAddresses: { type: Array, default: () => [] },
    defaultAddressId: { type: [Number, String, null], default: null },
    authenticated: { type: Boolean, default: false },
    user: { type: Object, default: () => ({ name: '', mobile: '' }) },
    old: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    labels: { type: Object, default: () => ({}) },
});

const defaultAddress = computed(
    () => props.savedAddresses.find((address) => address.id === props.defaultAddressId) || null
);

const selectedAddressId = ref(props.defaultAddressId);

const deliveryType = ref(props.old.delivery_type ?? 'home');
const name = ref(props.old.customer_name ?? defaultAddress.value?.name ?? props.user.name ?? '');
const phone = ref(props.old.customer_phone ?? defaultAddress.value?.phone ?? props.user.mobile ?? '');
const area = ref(props.old.customer_area ?? defaultAddress.value?.area ?? 'dhaka_inside');
const address = ref(props.old.customer_address ?? defaultAddress.value?.address ?? '');
const pickupPoint = ref(props.old.pickup_point ?? props.pickupPoints[0]?.value ?? '');
const notes = ref(props.old.notes ?? '');
const saveAddress = ref(true);

const isPickup = computed(() => deliveryType.value === 'pickup');

const deliveryFee = computed(() => {
    if (isPickup.value) return 0;
    if (props.subtotal >= props.freeDeliveryThreshold) return 0;

    return area.value === 'dhaka_outside' ? props.feeOutside : props.feeInside;
});

const total = computed(() => props.subtotal + deliveryFee.value);

/** Picking a saved address means there is nothing new to save. */
watch(selectedAddressId, (id) => {
    const chosen = props.savedAddresses.find((item) => item.id === id);

    if (!chosen) return;

    name.value = chosen.name;
    phone.value = chosen.phone;
    area.value = chosen.area ?? 'dhaka_inside';
    address.value = chosen.address;
    saveAddress.value = false;
});

function useNewAddress() {
    selectedAddressId.value = null;
    name.value = props.user.name ?? '';
    phone.value = props.user.mobile ?? '';
    area.value = 'dhaka_inside';
    address.value = '';
    saveAddress.value = true;
}

function label(key, fallback) {
    return props.labels[key] ?? fallback;
}

function error(field) {
    const messages = props.errors[field];

    return Array.isArray(messages) && messages.length ? messages[0] : null;
}
</script>

<template>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card admin-card p-4">
                <h4 class="mb-4" style="color: var(--primary-dark);">
                    <i class="bi bi-person"></i> {{ label('deliveryInfo', 'Delivery Information') }}
                </h4>

                <div class="mb-3">
                    <label class="form-label">{{ label('name', 'Full Name *') }}</label>
                    <input
                        v-model="name"
                        type="text"
                        name="customer_name"
                        class="form-control"
                        :class="{ 'is-invalid': error('customer_name') }"
                        :placeholder="label('namePlaceholder', 'Your name')"
                        required
                    >
                    <div v-if="error('customer_name')" class="invalid-feedback d-block">{{ error('customer_name') }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ label('phone', 'Phone Number *') }}</label>
                    <input
                        v-model="phone"
                        type="text"
                        name="customer_phone"
                        class="form-control"
                        :class="{ 'is-invalid': error('customer_phone') }"
                        placeholder="01XXXXXXXXX"
                        required
                    >
                    <div v-if="error('customer_phone')" class="invalid-feedback d-block">{{ error('customer_phone') }}</div>
                </div>

                <div v-if="authenticated && savedAddresses.length" class="mb-4">
                    <label class="form-label">{{ label('savedAddresses', 'Choose from Saved Addresses') }}</label>
                    <div class="row g-3">
                        <div v-for="saved in savedAddresses" :key="saved.id" class="col-md-6">
                            <div
                                class="address-card"
                                :class="{ active: saved.id === selectedAddressId }"
                                role="button"
                                @click="selectedAddressId = saved.id"
                            >
                                <div class="fw-bold">{{ saved.name }}</div>
                                <div class="small text-muted mb-1"><i class="bi bi-telephone"></i> {{ saved.phone }}</div>
                                <div class="small text-truncate" :title="saved.address">
                                    <i class="bi bi-geo-alt"></i> {{ saved.address }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div
                                class="address-card d-flex align-items-center justify-content-center h-100"
                                role="button"
                                @click="useNewAddress"
                            >
                                <div class="text-center">
                                    <i class="bi bi-plus-circle fs-4 text-primary"></i>
                                    <div class="small fw-bold mt-1">{{ label('newAddress', 'New Address') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="custom-radio-group shadow-sm">
                    <label class="custom-radio">
                        <input v-model="deliveryType" class="form-check-input" type="radio" name="delivery_type" value="home">
                        <span class="fw-bold"><i class="bi bi-truck"></i> {{ label('home', 'Home') }}</span>
                    </label>
                    <label class="custom-radio">
                        <input v-model="deliveryType" class="form-check-input" type="radio" name="delivery_type" value="pickup">
                        <span class="fw-bold"><i class="bi bi-geo-alt"></i> {{ label('pickup', 'Pickup') }}</span>
                    </label>
                </div>

                <div v-show="!isPickup">
                    <div class="mb-3">
                        <label class="form-label">{{ label('area', 'Delivery Area') }}</label>
                        <select v-model="area" name="customer_area" class="form-select border-0 shadow-sm">
                            <option value="dhaka_inside">{{ label('areaInside', 'Dhaka (Inside)') }}</option>
                            <option value="dhaka_outside">{{ label('areaOutside', 'Dhaka (Outside)') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ label('address', 'Full Address *') }}</label>
                        <textarea
                            v-model="address"
                            name="customer_address"
                            class="form-control border-0 shadow-sm"
                            :class="{ 'is-invalid': error('customer_address') }"
                            rows="3"
                            :placeholder="label('addressPlaceholder', 'Enter full address')"
                            :required="!isPickup"
                        ></textarea>
                        <div v-if="error('customer_address')" class="invalid-feedback d-block">{{ error('customer_address') }}</div>
                    </div>

                    <div v-if="authenticated && !selectedAddressId" class="form-check mb-3">
                        <input v-model="saveAddress" class="form-check-input" type="checkbox" name="save_address" id="save_address">
                        <label class="form-check-label small fw-bold" for="save_address">
                            {{ label('saveAddress', 'Save this address for future use') }}
                        </label>
                    </div>
                </div>

                <div v-show="isPickup">
                    <div class="mb-3">
                        <label class="form-label">{{ label('pickupPoint', 'Select Pickup Point *') }}</label>
                        <select v-model="pickupPoint" name="pickup_point" class="form-select border-0 shadow-sm">
                            <option v-for="point in pickupPoints" :key="point.value" :value="point.value">
                                {{ point.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ label('notes', 'Order Notes (Optional)') }}</label>
                    <textarea
                        v-model="notes"
                        name="notes"
                        class="form-control border-0 shadow-sm"
                        rows="2"
                        :placeholder="label('notesPlaceholder', 'Enter any special instructions')"
                    ></textarea>
                </div>

                <div class="alert alert-info py-2" style="font-size:.9rem;">
                    <i class="bi bi-info-circle"></i>
                    {{ label('paymentMethod', 'Payment Method:') }} <strong>Cash on Delivery (COD)</strong>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="cart-summary">
                <h4><i class="bi bi-receipt"></i> {{ label('yourOrder', 'Your Order') }}</h4>

                <div
                    v-for="item in items"
                    :key="item.product_name + item.variant_name"
                    class="d-flex justify-content-between align-items-center py-2 border-bottom"
                >
                    <div>
                        <strong style="font-size:.9rem;">{{ item.product_name }}</strong>
                        <br><small class="text-muted">{{ item.variant_name }} × {{ item.quantity }}</small>
                    </div>
                    <span class="fw-bold">{{ money(item.subtotal) }}</span>
                </div>

                <div class="summary-row mt-3">
                    <span>{{ label('subtotal', 'Subtotal') }}</span>
                    <span>{{ money(subtotal) }}</span>
                </div>
                <div class="summary-row">
                    <span>{{ label('delivery', 'Delivery') }}</span>
                    <span>
                        <span v-if="deliveryFee === 0" class="free-delivery-badge">{{ label('free', 'FREE') }}</span>
                        <template v-else>{{ money(deliveryFee) }}</template>
                    </span>
                </div>
                <div class="summary-row total">
                    <span>{{ label('total', 'Total') }}</span>
                    <span>{{ money(total) }}</span>
                </div>

                <button type="submit" class="btn-primary-custom w-100 justify-content-center mt-4" style="font-size:1.1rem;padding:16px;">
                    <i class="bi bi-check-circle"></i> {{ label('placeOrder', 'Place Order') }}
                </button>
            </div>
        </div>
    </div>
</template>
