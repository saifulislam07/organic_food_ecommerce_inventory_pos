<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    use SortsRecords;

    public function index(Request $request)
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->withCount('orders')
            // Cancelled orders are not revenue, so they stay out of the lifetime total.
            ->withSum(
                ['orders as orders_total' => fn ($query) => $query->where('status', '!=', 'cancelled')],
                'total'
            )
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%"));
            })
        ;

        $this->applySort($customers, $request, [
            'name' => 'name',
            'mobile' => 'mobile',
            'email' => 'email',
            'orders' => 'orders_count',
            'lifetime' => 'orders_total',
            'created_at' => 'created_at',
        ], 'created_at');

        $customers = $customers->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        abort_if($customer->isAdmin(), 404);

        $customer->load([
            'addresses',
            'orders' => fn ($query) => $query->latest(),
        ]);

        // Guest checkout is the norm here, so orders placed on the same phone
        // number without signing in would otherwise be invisible on this page.
        $guestOrders = Order::query()
            ->whereNull('user_id')
            ->when($customer->mobile, fn ($q) => $q->where('customer_phone', $customer->mobile))
            ->when(! $customer->mobile, fn ($q) => $q->whereRaw('1 = 0'))
            ->latest()
            ->get();

        return view('admin.customers.show', compact('customer', 'guestOrders'));
    }
}
