<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Receipt sent to the shopper the moment an order is placed. */
class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return NotificationChannels::forCustomer($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order {$this->order->order_number} received")
            ->markdown('emails.orders.placed', ['order' => $this->order]);
    }

    public function toSms(object $notifiable): string
    {
        return sprintf(
            'MohiPure: order %s received. Total Tk %s. We will call you to confirm.',
            $this->order->order_number,
            number_format($this->order->total)
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
