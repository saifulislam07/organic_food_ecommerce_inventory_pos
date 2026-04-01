<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 14px; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 8px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 32px; line-height: 32px; color: #2d6a4f; font-weight: bold; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #f8f9fa; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; font-size: 18px; color: #2d6a4f; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
        
        @media print {
            .no-print { display: none; }
            .invoice-box { box-shadow: none; border: none; padding: 0; }
        }
        .btn-print { background: #2d6a4f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 20px;">
        <a href="javascript:window.print()" class="btn-print">Print Invoice</a>
        <a href="{{ route('admin.orders.show', $order) }}" style="color: #666; margin-left: 15px;">Back to Order</a>
    </div>

    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">Mango Hut</td>
                            <td>
                                Invoice #: {{ $order->order_number }}<br>
                                Date: {{ $order->created_at->format('M d, Y') }}<br>
                                Status: {{ ucfirst($order->status) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>Customer:</strong><br>
                                {{ $order->customer_name }}<br>
                                {{ $order->customer_phone }}<br>
                                {{ $order->customer_address }}
                            </td>
                            <td>
                                <strong>From:</strong><br>
                                Mango Hut Organic Store<br>
                                Chapainawabganj, Bangladesh<br>
                                support@mangohut.com.bd
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>

            @foreach($order->items as $item)
            <tr class="item">
                <td>
                    {{ $item->product_name }} ({{ $item->product_weight }}) x {{ $item->quantity }}
                </td>
                <td>৳{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
            @endforeach

            <tr class="item">
                <td>Subtotal</td>
                <td>৳{{ number_format($order->subtotal) }}</td>
            </tr>

            <tr class="item">
                <td>Delivery Charge</td>
                <td>৳{{ number_format($order->delivery_charge) }}</td>
            </tr>

            <tr class="total">
                <td></td>
                <td>Total: ৳{{ number_format($order->total_amount) }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Thank you for shopping with Mango Hut! Stay Organic, Stay Healthy.</p>
            <p>This is a computer generated invoice.</p>
        </div>
    </div>
</body>
</html>
