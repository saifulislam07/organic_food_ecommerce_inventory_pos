<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th class="ps-4">Order #</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-end pe-4">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td class="ps-4">
                    <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none">
                        {{ $order->order_number }}
                    </a>
                </td>
                <td class="text-muted small">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                <td>{!! $order->status_badge !!}</td>
                <td class="text-end pe-4 fw-bold">৳{{ number_format($order->total) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">{{ $empty }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
