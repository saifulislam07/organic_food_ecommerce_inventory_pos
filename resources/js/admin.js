import { startIslands } from './shared/islands';

import PosApp from './admin/components/PosApp.vue';
import InventoryTable from './admin/components/InventoryTable.vue';
import VariantRepeater from './admin/components/VariantRepeater.vue';
import PurchaseForm from './admin/components/PurchaseForm.vue';
import AdjustmentForm from './admin/components/AdjustmentForm.vue';
import OrderStatusControl from './admin/components/OrderStatusControl.vue';
import ImageUpload from './admin/components/ImageUpload.vue';

const components = {
    PosApp,
    InventoryTable,
    VariantRepeater,
    PurchaseForm,
    AdjustmentForm,
    OrderStatusControl,
    ImageUpload,
};

window.mountVueIslands = startIslands(components);
