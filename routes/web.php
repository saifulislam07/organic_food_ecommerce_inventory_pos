<?php

use App\Http\Controllers\Admin\AdminAdjustmentController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminChatSettingController;
use App\Http\Controllers\Admin\AdminComboController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminExpenseController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\Admin\AdminLandingPageController;
use App\Http\Controllers\Admin\AdminMailSettingController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminPOSController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminPurchaseController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSeoSettingController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSmsSettingController;
use App\Http\Controllers\Admin\AdminSupplierController;
use App\Http\Controllers\Admin\AdminUnitController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingOrderController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/about', fn () => view('pages.about'))->name('about');
Route::get('/contact', fn () => view('pages.contact'))->name('contact');
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// Cart Routes (AJAX)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::post('/update', [CartController::class, 'update'])->name('update');
    Route::post('/remove', [CartController::class, 'remove'])->name('remove');
    Route::get('/count', [CartController::class, 'count'])->name('count');
    Route::get('/mini', [CartController::class, 'mini'])->name('mini');
});

// Checkout Routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/order-success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');

/*
 | Campaign landing pages.
 |
 | Under their own prefix so a campaign slug can never collide with the
 | catch-all page route at the bottom of this file. Ordering here does not
 | matter for that reason, but they are registered early anyway.
 */
Route::prefix(config('landing.prefix', 'lp'))->name('landing.')->group(function () {
    Route::get('{slug}', [LandingPageController::class, 'show'])->name('show');

    // Public, unauthenticated and writes to the database — ad traffic brings
    // bots, so the one route that creates an order is rate limited.
    Route::post('{slug}/order', [LandingOrderController::class, 'store'])
        ->middleware('throttle:'.config('landing.order_rate_limit', 8).',1')
        ->name('order');

    Route::get('{slug}/thank-you/{orderNumber}', [LandingOrderController::class, 'thankYou'])
        ->name('thankyou');
});

// Customer Routes
Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders/{orderNumber}', [CustomerDashboardController::class, 'show'])->name('orders.show');
    Route::get('/orders/{orderNumber}/invoice', [CustomerDashboardController::class, 'invoice'])->name('orders.invoice');
});

// Admin Routes
Route::middleware(['auth', 'is_admin', 'admin_can'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Bulk delete: registered before the resources so /bulk is not read
    // as a record id.
    Route::delete('products/bulk', [AdminProductController::class, 'bulkDestroy'])->name('products.bulkDestroy');
    Route::delete('categories/bulk', [AdminCategoryController::class, 'bulkDestroy'])->name('categories.bulkDestroy');
    Route::delete('units/bulk', [AdminUnitController::class, 'bulkDestroy'])->name('units.bulkDestroy');
    Route::delete('suppliers/bulk', [AdminSupplierController::class, 'bulkDestroy'])->name('suppliers.bulkDestroy');
    Route::delete('expenses/bulk', [AdminExpenseController::class, 'bulkDestroy'])->name('expenses.bulkDestroy');
    Route::delete('pages/bulk', [AdminPageController::class, 'bulkDestroy'])->name('pages.bulkDestroy');
    Route::delete('landing-pages/bulk', [AdminLandingPageController::class, 'bulkDestroy'])->name('landing-pages.bulkDestroy');
    Route::delete('combos/bulk', [AdminComboController::class, 'bulkDestroy'])->name('combos.bulkDestroy');
    Route::delete('purchases/bulk', [AdminPurchaseController::class, 'bulkDestroy'])->name('purchases.bulkDestroy');
    Route::delete('adjustments/bulk', [AdminAdjustmentController::class, 'bulkDestroy'])->name('adjustments.bulkDestroy');
    Route::delete('users/bulk', [AdminUserController::class, 'bulkDestroy'])->name('users.bulkDestroy');
    Route::delete('roles/bulk', [AdminRoleController::class, 'bulkDestroy'])->name('roles.bulkDestroy');
    Route::resource('products', AdminProductController::class);
    Route::get('combos', [AdminComboController::class, 'index'])->name('combos.index');
    Route::get('combos/create', [AdminComboController::class, 'create'])->name('combos.create');
    Route::post('combos', [AdminComboController::class, 'store'])->name('combos.store');
    Route::get('combos/{product}/edit', [AdminComboController::class, 'edit'])->name('combos.edit');
    Route::put('combos/{product}', [AdminComboController::class, 'update'])->name('combos.update');
    Route::delete('combos/{product}', [AdminComboController::class, 'destroy'])->name('combos.destroy');
    Route::resource('categories', AdminCategoryController::class);
    Route::resource('units', AdminUnitController::class)->except(['show']);
    Route::resource('expenses', AdminExpenseController::class);

    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::patch('/inventory/{variant}', [AdminInventoryController::class, 'updateStock'])->name('inventory.update');

    Route::get('/pos', [AdminPOSController::class, 'index'])->name('pos.index');
    Route::post('/pos', [AdminPOSController::class, 'store'])->name('pos.store');
    Route::get('/pos/search', [AdminPOSController::class, 'search'])->name('pos.search');

    Route::resource('suppliers', AdminSupplierController::class);
    Route::resource('purchases', AdminPurchaseController::class)->except(['edit', 'update']);
    Route::resource('adjustments', AdminAdjustmentController::class)->except(['edit', 'update']);

    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}', [AdminNotificationController::class, 'read'])->name('notifications.read');

    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    // Settings & Pages
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/mail', [AdminMailSettingController::class, 'edit'])->name('settings.mail.edit');
    Route::post('/settings/mail', [AdminMailSettingController::class, 'update'])->name('settings.mail.update');
    Route::post('/settings/mail/test', [AdminMailSettingController::class, 'test'])->name('settings.mail.test');
    Route::get('/settings/sms', [AdminSmsSettingController::class, 'edit'])->name('settings.sms.edit');
    Route::post('/settings/sms', [AdminSmsSettingController::class, 'update'])->name('settings.sms.update');
    Route::post('/settings/sms/test', [AdminSmsSettingController::class, 'test'])->name('settings.sms.test');
    Route::get('/settings/seo', [AdminSeoSettingController::class, 'edit'])->name('settings.seo.edit');
    Route::post('/settings/seo', [AdminSeoSettingController::class, 'update'])->name('settings.seo.update');
    Route::get('/reports/profit-loss', [AdminReportController::class, 'profitLoss'])->name('reports.profitLoss');
    Route::get('/settings/chat', [AdminChatSettingController::class, 'edit'])->name('settings.chat.edit');
    Route::post('/settings/chat', [AdminChatSettingController::class, 'update'])->name('settings.chat.update');
    Route::resource('pages', AdminPageController::class)->except(['show']);

    // Copying a running campaign is how the next one gets built, so it is a
    // first-class action rather than something done by hand.
    Route::post('landing-pages/{landing_page}/duplicate', [AdminLandingPageController::class, 'duplicate'])
        ->name('landing-pages.duplicate');
    Route::resource('landing-pages', AdminLandingPageController::class)->except(['show']);
    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::resource('roles', AdminRoleController::class)->except(['show']);

    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Language Switcher
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session()->put('locale', $locale);
    }

    return back();
})->name('lang.switch');

require __DIR__.'/auth.php';

/*
 | Pretty page URLs: /about-us rather than /p/about-us.
 |
 | This is a catch-all, so it MUST stay the last route in the file. Registered
 | any earlier it swallows every path declared below it — /cart and /checkout
 | included — and hands them to PageController instead.
 */
Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('pages.show');
