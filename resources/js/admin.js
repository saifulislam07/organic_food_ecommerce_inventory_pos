import { startIslands } from './shared/islands';
import { initBulkDelete } from './admin/bulk';
import { initEditors } from './admin/editor';
import { flashToasts, initCopyButtons, interceptConfirmForms } from './admin/ui';

import PosApp from './admin/components/PosApp.vue';
import InventoryTable from './admin/components/InventoryTable.vue';
import VariantRepeater from './admin/components/VariantRepeater.vue';
import PurchaseForm from './admin/components/PurchaseForm.vue';
import AdjustmentForm from './admin/components/AdjustmentForm.vue';
import OrderStatusControl from './admin/components/OrderStatusControl.vue';
import ImageUpload from './admin/components/ImageUpload.vue';
import ProductGallery from './admin/components/ProductGallery.vue';
import ComboComposer from './admin/components/ComboComposer.vue';
import LandingItems from './admin/components/LandingItems.vue';
import LandingContentBlocks from './admin/components/LandingContentBlocks.vue';
import AdminDialogs from './admin/components/AdminDialogs.vue';

const components = {
    PosApp,
    InventoryTable,
    VariantRepeater,
    PurchaseForm,
    AdjustmentForm,
    OrderStatusControl,
    ImageUpload,
    ProductGallery,
    ComboComposer,
    LandingItems,
    LandingContentBlocks,
    AdminDialogs,
};

window.mountVueIslands = startIslands(components);

// data-confirm on any form opens the themed dialog instead of window.confirm.
interceptConfirmForms();

// Laravel's flash messages arrive as toasts rather than a static alert bar.
function bootAdmin() {
    flashToasts();
    initBulkDelete();
    initEditors();
    initCopyButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdmin);
} else {
    bootAdmin();
}
