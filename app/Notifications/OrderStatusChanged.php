<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** Statuses worth interrupting a customer for. */
    private const ANNOUNCED = ['confirmed', 'shipped', 'delivered', 'cancelled'];

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {}

    public static function isWorthSending(string $status): bool
    {
        return in_array($status, self::ANNOUNCED, true);
    }

    public function via(object $notifiable): array
    {
        return NotificationChannels::forCustomer($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order {$this->order->order_number} is {$this->order->status}")
            ->markdown('emails.orders.status', [
                'order' => $this->order,
                'headline' => $this->headline(),
            ]);
    }

    public function toSms(object $notifiable): string
    {
        return "Mango Hut: {$this->headline()} (order {$this->order->order_number}).";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'previous_status' => $this->previousStatus,
        ];
    }

    private function headline(): string
    {
        return match ($this->order->status) {
            'confirmed' => 'Your order is confirmed',
            'shipped' => 'Your order is on the way',
            'delivered' => 'Your order has been delivered',
            'cancelled' => 'Your order has been cancelled',
            default => "Your order is now {$this->order->status}",
        };
    }
}
