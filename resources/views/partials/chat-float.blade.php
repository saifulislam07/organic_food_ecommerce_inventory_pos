{{--
    The floating chat buttons. Driven by Admin → Settings → WhatsApp & Messenger;
    a button with nothing behind it simply does not render.
--}}
@php
    $waUrl = \App\Support\ChatSettings::whatsappUrl();
    $fbUrl = \App\Support\ChatSettings::messengerUrl();
    $side = \App\Support\ChatSettings::position();
    $bn = app()->getLocale() === 'bn';
@endphp

@if($waUrl || $fbUrl)
<div class="chat-float chat-float--{{ $side }}">
    @if($fbUrl)
        <a href="{{ $fbUrl }}" class="chat-float__btn chat-float__btn--messenger" target="_blank" rel="noopener"
           aria-label="{{ $bn ? 'মেসেঞ্জারে কথা বলুন' : 'Chat on Messenger' }}">
            <i class="bi bi-messenger"></i>
            <span class="chat-float__label">{{ $bn ? 'মেসেঞ্জার' : 'Messenger' }}</span>
        </a>
    @endif

    @if($waUrl)
        <a href="{{ $waUrl }}" class="chat-float__btn chat-float__btn--whatsapp" target="_blank" rel="noopener"
           aria-label="{{ $bn ? 'হোয়াটসঅ্যাপে কথা বলুন' : 'Chat on WhatsApp' }}">
            <i class="bi bi-whatsapp"></i>
            <span class="chat-float__label">{{ $bn ? 'হোয়াটসঅ্যাপ' : 'WhatsApp' }}</span>
        </a>
    @endif
</div>
@endif
