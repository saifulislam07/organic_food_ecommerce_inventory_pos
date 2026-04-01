<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminExpenseController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminSupplierController;
use App\Http\Controllers\Admin\AdminPurchaseController;
use App\Http\Controllers\Admin\AdminAdjustmentController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('pages.show');
Route::get('/about', fn() => view('pages.about'))->name('about');
Route::get('/contact', fn() => view('pages.contact'))->name('contact');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);


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

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', AdminProductController::class);
    Route::resource('categories', AdminCategoryController::class);
    Route::resource('expenses', AdminExpenseController::class);

    Route::get('/inventory', [\App\Http\Controllers\Admin\AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::patch('/inventory/{variant}', [\App\Http\Controllers\Admin\AdminInventoryController::class, 'updateStock'])->name('inventory.update');

    Route::get('/pos', [\App\Http\Controllers\Admin\AdminPOSController::class, 'index'])->name('pos.index');
    Route::post('/pos', [\App\Http\Controllers\Admin\AdminPOSController::class, 'store'])->name('pos.store');
    Route::get('/pos/search', [\App\Http\Controllers\Admin\AdminPOSController::class, 'search'])->name('pos.search');

    Route::resource('suppliers', \App\Http\Controllers\Admin\AdminSupplierController::class);
    Route::resource('purchases', \App\Http\Controllers\Admin\AdminPurchaseController::class)->except(['edit', 'update']);
    Route::resource('adjustments', \App\Http\Controllers\Admin\AdminAdjustmentController::class)->except(['edit', 'update']);

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    // Settings & Pages
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::resource('pages', AdminPageController::class);

    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
});

// Language Switcher
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session()->put('locale', $locale);
    }
    return back();
})->name('lang.switch');

require __DIR__.'/auth.php';
