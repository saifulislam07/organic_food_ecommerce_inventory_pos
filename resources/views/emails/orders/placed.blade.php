<x-mail::message>
# Thank you for your order

Hi {{ $order->customer_name }}, we have received order **{{ $order->order_number }}**.

<x-mail::table>
| Item | Qty | Amount |
| :--- | :-: | -----: |
@foreach($order->items as $item)
| {{ $item->product_name }} ({{ $item->variant_name }}) | {{ $item->quantity }} | ৳{{ number_format($item->total) }} |
@endforeach
| **Delivery** | | ৳{{ number_format($order->delivery_charge) }} |
| **Total** | | **৳{{ number_format($order->total) }}** |
</x-mail::table>

We will call you on {{ $order->customer_phone }} to confirm delivery.

Payment method: **Cash on Delivery**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
