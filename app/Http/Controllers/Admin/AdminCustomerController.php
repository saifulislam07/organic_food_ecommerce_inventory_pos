<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    public function edit(User $customer)
    {
        abort_if($customer->isAdmin(), 404);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer)
    {
        abort_if($customer->isAdmin(), 404);

        // Mobile stays required: it is how customers sign in and how guest
        // orders are matched to their account.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
            'mobile' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($customer->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:20'],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'email' => ($validated['email'] ?? null) ?: null,
            'mobile' => $validated['mobile'],
        ]);

        // Blank means "leave the current password alone".
        if (filled($validated['password'] ?? null)) {
            $customer->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('admin.customers.show', $customer)->with('success', "{$customer->name} updated.");
    }

    public function toggleBlock(User $customer)
    {
        abort_if($customer->isAdmin(), 404);

        if ($customer->isBlocked()) {
            $customer->forceFill(['blocked_at' => null])->save();

            return back()->with('success', "{$customer->name} can sign in again.");
        }

        $customer->forceFill(['blocked_at' => now()])->save();

        // End any session they already have, rather than waiting for it to expire.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $customer->id)->delete();
        }

        return back()->with('success', "{$customer->name} is blocked and can no longer sign in.");
    }
}
