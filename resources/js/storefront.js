import { startIslands } from './shared/islands';
import { addToCart, notify, refreshCount } from './storefront/cart';
import { bindLoadMore } from './storefront/loadMore';

import CartBadge from './storefront/components/CartBadge.vue';
import CartToast from './storefront/components/CartToast.vue';
import AddToCartButton from './storefront/components/AddToCartButton.vue';
import ProductPurchase from './storefront/components/ProductPurchase.vue';
import CartPage from './storefront/components/CartPage.vue';
import CheckoutForm from './storefront/components/CheckoutForm.vue';
import ConfirmDeleteAccount from './storefront/components/ConfirmDeleteAccount.vue';
import ProductGalleryViewer from './storefront/components/ProductGalleryViewer.vue';

const components = {
    CartBadge,
    CartToast,
    AddToCartButton,
    ProductPurchase,
    CartPage,
    CheckoutForm,
    ConfirmDeleteAccount,
    ProductGalleryViewer,
};

const mountIslands = startIslands(components);

window.mountVueIslands = mountIslands;

// Kept for any Blade still calling the old globals inline.
window.addToCart = addToCart;
window.showToast = notify;

/** Navbar shadow on scroll — replaces the old jQuery handler. */
function bindNavbarScroll() {
    const navbar = document.getElementById('mainNavbar');

    if (!navbar) return;

    const sync = () => navbar.classList.toggle('scrolled', window.scrollY > 50);

    sync();
    window.addEventListener('scroll', sync, { passive: true });
}

/** Reveal-on-scroll for .fade-up elements, shared by every storefront page. */
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.1 }
);

function observeNewReveals(root = document) {
    root.querySelectorAll('.fade-up:not(.visible)').forEach((el) => revealObserver.observe(el));
}

window.initScrollReveal = observeNewReveals;

function boot() {
    bindNavbarScroll();
    observeNewReveals();
    refreshCount();

    bindLoadMore((grid) => {
        mountIslands(grid);
        observeNewReveals(grid);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
