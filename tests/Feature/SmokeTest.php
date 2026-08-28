<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every GET route renders. Cheap coverage for the pages that have no behaviour
 * worth a dedicated test but would still break loudly if a view went stale.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): array
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 কেজি',
            'price' => 1200,
            'stock' => 10,
        ]);

        return [$category, $product, $variant];
    }

    private function seedOrder(User $user): Order
    {
        [, $product, $variant] = $this->seedCatalog();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'subtotal' => 1200,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 1260,
            'status' => 'pending',
            'payment_method' => 'cod',
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Himsagar Mango',
            'variant_name' => '3 কেজি',
            'quantity' => 1,
            'unit_price' => 1200,
            'total' => 1200,
        ]);

        return $order;
    }

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'shop' => ['/shop'],
            'cart' => ['/cart'],
            'about' => ['/about'],
            'contact' => ['/contact'],
            'sitemap' => ['/sitemap.xml'],
            'login' => ['/login'],
            'register' => ['/register'],
            'admin login' => ['/admin/login'],
            'forgot password' => ['/forgot-password'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders(string $url): void
    {
        $this->seedCatalog();

        $this->get($url)->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'products' => ['/admin/products'],
            'product create' => ['/admin/products/create'],
            'categories' => ['/admin/categories'],
            'category create' => ['/admin/categories/create'],
            'combos' => ['/admin/combos'],
            'combo create' => ['/admin/combos/create'],
            'units' => ['/admin/units'],
            'unit create' => ['/admin/units/create'],
            'orders' => ['/admin/orders'],
            'customers' => ['/admin/customers'],
            'notifications' => ['/admin/notifications'],
            'pos' => ['/admin/pos'],
            'inventory' => ['/admin/inventory'],
            'suppliers' => ['/admin/suppliers'],
            'supplier create' => ['/admin/suppliers/create'],
            'purchases' => ['/admin/purchases'],
            'purchase create' => ['/admin/purchases/create'],
            'adjustments' => ['/admin/adjustments'],
            'adjustment create' => ['/admin/adjustments/create'],
            'expenses' => ['/admin/expenses'],
            'expense create' => ['/admin/expenses/create'],
            'pages' => ['/admin/pages'],
            'page create' => ['/admin/pages/create'],
            'settings' => ['/admin/settings'],
            'mail settings' => ['/admin/settings/mail'],
            'sms settings' => ['/admin/settings/sms'],
            'seo settings' => ['/admin/settings/seo'],
            'users' => ['/admin/users'],
            'user create' => ['/admin/users/create'],
            'roles' => ['/admin/roles'],
            'role create' => ['/admin/roles/create'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_renders(string $url): void
    {
        $this->seedCatalog();

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_admin_order_detail_and_invoice_render(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $order = $this->seedOrder($admin);

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))->assertOk();
    }

    public function test_admin_edit_screens_render(): void
    {
        $admin = User::factory()->superAdmin()->create();
        [$category, $product] = $this->seedCatalog();

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.edit', $category))->assertOk();
    }

    public function test_customer_dashboard_order_and_invoice_render(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->seedOrder($customer);

        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();
        $this->actingAs($customer)->get(route('customer.orders.show', $order->order_number))->assertOk();
        $this->actingAs($customer)->get(route('customer.orders.invoice', $order->order_number))->assertOk();
    }

    public function test_profile_page_renders(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk();
    }

    public function test_product_detail_renders(): void
    {
        [, $product] = $this->seedCatalog();

        $this->get(route('product.show', $product->slug))->assertOk();
    }

    public function test_checkout_renders_once_the_cart_has_something_in_it(): void
    {
        [, $product, $variant] = $this->seedCatalog();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->get(route('checkout'))->assertOk();
    }

    public function test_a_supplier_can_be_created_and_listed(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.suppliers.store'), ['name' => 'Chapai Traders', 'phone' => '01700000000'])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['name' => 'Chapai Traders']);
    }

    public function test_an_expense_can_be_created(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.expenses.store'), [
                'title' => 'Packaging',
                'category' => 'supplies',
                'amount' => 2500,
                'expense_date' => date('Y-m-d'),
            ])
            ->assertRedirect(route('admin.expenses.index'));

        $this->assertDatabaseHas('expenses', ['title' => 'Packaging']);
    }

    public function test_a_purchase_adds_stock_and_an_adjustment_removes_it(): void
    {
        $admin = User::factory()->superAdmin()->create();
        [, , $variant] = $this->seedCatalog();
        $supplier = Supplier::create(['name' => 'Chapai Traders']);

        $this->actingAs($admin)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'purchase_price' => 800,
            'quantity' => 5,
            'purchase_date' => date('Y-m-d'),
        ])->assertRedirect(route('admin.purchases.index'));

        $this->assertSame(15, $variant->fresh()->stock);

        $this->actingAs($admin)->post(route('admin.adjustments.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'type' => 'damage',
            'reason' => 'Bruised in transit',
            'adjustment_date' => date('Y-m-d'),
        ])->assertRedirect(route('admin.adjustments.index'));

        $this->assertSame(12, $variant->fresh()->stock);
    }
}
