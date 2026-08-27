<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerListTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create(['role' => 'admin']);
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'delivery_charge' => 0,
            'total' => 1000,
            'status' => 'delivered',
            'payment_method' => 'cod',
            'source' => 'web',
        ], $attributes));
    }

    public function test_it_lists_customers_with_order_count_and_lifetime_value(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Rahim Uddin']);

        $this->order(['user_id' => $customer->id, 'total' => 1200]);
        $this->order(['user_id' => $customer->id, 'total' => 800]);

        $response = $this->actingAs($this->admin())->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertSee('Rahim Uddin');

        $row = $response->viewData('customers')->firstOrFail();

        $this->assertSame(2, $row->orders_count);
        $this->assertEquals(2000, $row->orders_total);
    }

    public function test_cancelled_orders_do_not_count_toward_lifetime_value(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->order(['user_id' => $customer->id, 'total' => 1000]);
        $this->order(['user_id' => $customer->id, 'total' => 5000, 'status' => 'cancelled']);

        $row = $this->actingAs($this->admin())
            ->get(route('admin.customers.index'))
            ->viewData('customers')
            ->firstOrFail();

        $this->assertSame(2, $row->orders_count, 'The order still happened, so it is still counted.');
        $this->assertEquals(1000, $row->orders_total, 'But a cancelled order is not revenue.');
    }

    public function test_admins_are_not_listed_as_customers(): void
    {
        User::factory()->create(['role' => 'customer', 'name' => 'A Customer']);

        // The admin's own name is in the topbar, so check the collection itself.
        $listed = $this->actingAs($this->admin())
            ->get(route('admin.customers.index'))
            ->viewData('customers');

        $this->assertCount(1, $listed);
        $this->assertFalse($listed->contains('id', $this->admin()->id));
    }

    public function test_it_can_be_searched_by_name_email_or_mobile(): void
    {
        User::factory()->create(['role' => 'customer', 'name' => 'Rahim', 'mobile' => '01711111111']);
        User::factory()->create(['role' => 'customer', 'name' => 'Karim', 'mobile' => '01822222222']);

        foreach (['Karim', '01822222222'] as $term) {
            $found = $this->actingAs($this->admin())
                ->get(route('admin.customers.index', ['search' => $term]))
                ->viewData('customers');

            $this->assertCount(1, $found, "search for {$term}");
            $this->assertSame('Karim', $found->first()->name);
        }
    }

    public function test_the_detail_page_shows_their_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'mobile' => '01711111111']);
        $order = $this->order(['user_id' => $customer->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_guest_orders_on_the_same_mobile_are_surfaced_separately(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'mobile' => '01711111111']);

        $linked = $this->order(['user_id' => $customer->id]);
        $guest = $this->order(['user_id' => null, 'customer_phone' => '01711111111']);
        $other = $this->order(['user_id' => null, 'customer_phone' => '01999999999']);

        $response = $this->actingAs($this->admin())->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee($linked->order_number);
        $response->assertSee($guest->order_number);
        $response->assertDontSee($other->order_number);
    }

    public function test_a_customer_without_a_mobile_number_matches_no_guest_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'mobile' => null]);
        $this->order(['user_id' => null, 'customer_phone' => '01711111111']);

        $response = $this->actingAs($this->admin())->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('guestOrders'));
    }

    public function test_the_detail_page_does_not_expose_another_admin(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin())
            ->get(route('admin.customers.show', $other))
            ->assertNotFound();
    }

    public function test_a_customer_cannot_browse_the_customer_list(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }
}
