<x-mail::message>
# {{ $headline }}

Hi {{ $order->customer_name }}, order **{{ $order->order_number }}** placed on
{{ $order->created_at->format('d M Y') }} is now **{{ ucfirst($order->status) }}**.

<x-mail::panel>
Total: ৳{{ number_format($order->total) }}<br>
Delivery to: {{ $order->customer_address }}
</x-mail::panel>

If anything looks wrong, just reply to this email or call us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
