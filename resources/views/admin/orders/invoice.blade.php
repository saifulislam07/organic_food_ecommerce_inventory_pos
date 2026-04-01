<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() == 'bn' ? 'ইনভয়েস' : 'Invoice' }} - {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, 'Hind Siliguri'; color: #333; line-height: 1.5; margin: 0; padding: 20px; background: #f0f0f0; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 50px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2d6a4f; padding-bottom: 30px; margin-bottom: 30px; }
        .brand-section h1 { color: #2d6a4f; margin: 0; font-size: 32px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .brand-section p { margin: 5px 0 0; color: #666; font-size: 14px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { margin: 0; color: #333; font-size: 24px; font-weight: 700; }
        .invoice-meta p { margin: 5px 0; color: #666; font-size: 14px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .info-block h3 { font-size: 14px; text-transform: uppercase; color: #2d6a4f; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 12px; font-weight: 700; letter-spacing: 0.5px; }
        .info-block p { margin: 4px 0; font-size: 14px; color: #444; }

        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th { background: #f8fdfa; border-bottom: 2px solid #2d6a4f; padding: 12px 15px; text-align: left; font-size: 13px; font-weight: 700; text-transform: uppercase; color: #2d6a4f; }
        .invoice-table td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        .invoice-table .text-right { text-align: right; }
        .invoice-table .text-center { text-align: center; }

        .summary-section { display: flex; justify-content: flex-end; }
        .summary-table { width: 250px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .summary-row.total { border-top: 2px solid #2d6a4f; margin-top: 10px; padding-top: 12px; font-weight: 800; font-size: 18px; color: #2d6a4f; }
        
        .footer { margin-top: 60px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        .footer p { margin: 4px 0; }

        .no-print-area { text-align: center; margin-bottom: 30px; position: sticky; top: 10px; z-index: 100; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 14px; transition: 0.2s; cursor: pointer; border: none; }
        .btn-print { background: #2d6a4f; color: white; }
        .btn-print:hover { background: #1b4332; }
        .btn-back { background: #6c757d; color: white; margin-left: 10px; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-container { box-shadow: none; border: none; width: 100%; max-width: none; padding: 0; }
            .no-print-area { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print-area">
    <button onclick="window.print()" class="btn btn-print">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm6 4v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-1h7z"/></svg>
        {{ app()->getLocale() == 'bn' ? 'প্রিন্ট ইনভয়েস' : 'Print Invoice' }}
    </button>
    <a href="{{ auth()->user()->isAdmin() ? route('admin.orders.show', $order) : route('customer.orders.show', $order->order_number) }}" class="btn btn-back">
        {{ app()->getLocale() == 'bn' ? 'ফিরে যান' : 'Back' }}
    </a>
</div>

<div class="invoice-container">
    <div class="invoice-header">
        <div class="brand-section">
            <h1>Mango Hut</h1>
            <p>{{ app()->getLocale() == 'bn' ? 'আপনার বিশ্বস্ত অর্গানিক ফুড পার্টনার' : 'Your Trusted Organic Food Partner' }}</p>
        </div>
        <div class="invoice-meta">
            <h2>{{ app()->getLocale() == 'bn' ? 'ইনভয়েস' : 'Invoice' }}</h2>
            <p><strong>#{{ $order->order_number }}</strong></p>
            <p>{{ app()->getLocale() == 'bn' ? 'তারিখ:' : 'Date:' }} {{ $order->created_at->format('d M, Y') }}</p>
            <p>{{ app()->getLocale() == 'bn' ? 'অবস্থা:' : 'Status:' }} {{ strtoupper($order->status) }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h3>{{ app()->getLocale() == 'bn' ? 'কার কাছে পাঠানো হবে' : 'Bill To' }}</h3>
            <p><strong>{{ $order->customer_name }}</strong></p>
            <p>{{ $order->customer_phone }}</p>
            <p>{{ $order->customer_address }}</p>
            @if($order->customer_area)
                <p>{{ app()->getLocale() == 'bn' ? 'এলাকা:' : 'Area:' }} {{ ucwords(str_replace('_', ' ', $order->customer_area)) }}</p>
            @endif
        </div>
        <div class="info-block">
            <h3>{{ app()->getLocale() == 'bn' ? 'প্রেরক' : 'Ship From' }}</h3>
            <p><strong>Mango Hut</strong></p>
            <p>{{ \App\Models\Setting::get('phone', '01716-952365') }}</p>
            <p>{{ \App\Models\Setting::get('address', 'Chapainawabganj, Rajshahi') }}</p>
            <p>www.mangohut.com.bd</p>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th>{{ app()->getLocale() == 'bn' ? 'পণ্যের নাম' : 'Description' }}</th>
                <th class="text-center">{{ app()->getLocale() == 'bn' ? 'পরিমাণ' : 'Qty' }}</th>
                <th class="text-right">{{ app()->getLocale() == 'bn' ? 'একক মূল্য' : 'Unit Price' }}</th>
                <th class="text-right">{{ app()->getLocale() == 'bn' ? 'মোট' : 'Total' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->variant_name)
                        <br><small style="color: #666;">{{ $item->variant_name }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">৳{{ number_format($item->unit_price) }}</td>
                <td class="text-right">৳{{ number_format($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-table">
            <div class="summary-row">
                <span>{{ app()->getLocale() == 'bn' ? 'সাবটোটাল' : 'Subtotal' }}</span>
                <span>৳{{ number_format($order->subtotal) }}</span>
            </div>
            <div class="summary-row">
                <span>{{ app()->getLocale() == 'bn' ? 'ডেলিভারি চার্জ' : 'Delivery Charge' }}</span>
                <span>৳{{ number_format($order->delivery_charge) }}</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="summary-row">
                <span>{{ app()->getLocale() == 'bn' ? 'ডিসকাউন্ট' : 'Discount' }}</span>
                <span style="color: #dc3545;">-৳{{ number_format($order->discount_amount) }}</span>
            </div>
            @endif
            <div class="summary-row total">
                <span>{{ app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Grand Total' }}</span>
                <span>৳{{ number_format($order->total) }}</span>
            </div>
        </div>
    </div>

    @if($order->notes)
    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px;">
        <h4 style="font-size: 14px; margin-bottom: 5px;">{{ app()->getLocale() == 'bn' ? 'অতিরিক্ত তথ্য:' : 'Notes:' }}</h4>
        <p style="font-size: 13px; color: #666; margin: 0;">{{ $order->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>{{ app()->getLocale() == 'bn' ? 'ম্যাংগো হাট থেকে কেনাকাটা করার জন্য ধন্যবাদ!' : 'Thank you for shopping with Mango Hut!' }}</p>
        <p>{{ app()->getLocale() == 'bn' ? 'এটি একটি কম্পিউটার জেনারেটেড ইনভয়েস' : 'This is a computer generated invoice' }}</p>
    </div>
</div>

</body>
</html>
