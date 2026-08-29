<x-mail::message>
# SMTP is working

This is a test message sent from your MohiPure admin panel.

If you are reading it, your email settings are correct and the shop can send
order confirmations and status updates.

<x-mail::subcopy>
Sent {{ now()->format('d M Y, h:i A') }} from {{ config('app.url') }}
</x-mail::subcopy>
</x-mail::message>
