@extends('layouts.frontend')
@section('title', 'Contact Us – BaburhashiBD')

@push('styles')
<style>
    .contact-panel {
        background: linear-gradient(135deg, var(--primary-darker), var(--primary-dark) 55%, var(--primary));
        border-radius: var(--radius-lg);
        padding: 48px 36px;
        color: #fff;
        height: 100%;
    }
    .contact-panel .section-badge {
        background: rgba(255, 255, 255, 0.12);
        color: var(--accent-gold);
    }
    .contact-panel h3 {
        font-weight: 700;
        margin-bottom: 10px;
    }
    .contact-panel > p {
        color: rgba(255, 255, 255, 0.75);
        margin-bottom: 32px;
    }
    .contact-info-list {
        list-style: none;
        padding: 0;
        margin: 0 0 32px;
    }
    .contact-info-list li {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 22px;
    }
    .contact-info-list .icon-circle {
        flex-shrink: 0;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: var(--accent-gold);
    }
    .contact-info-list .label {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255, 255, 255, 0.55);
        margin-bottom: 2px;
    }
    .contact-info-list a,
    .contact-info-list span.value {
        color: #fff;
        text-decoration: none;
        font-weight: 600;
    }
    .contact-info-list a:hover { color: var(--accent-gold); }
    .contact-form-card {
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        padding: 48px 40px;
        height: 100%;
    }
    .contact-form-card .form-control {
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
        padding: 12px 16px;
    }
    .contact-form-card .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.12);
    }
    @media (max-width: 991.98px) {
        .contact-panel { margin-bottom: 24px; }
    }
</style>
@endpush

@section('content')
@include('partials.page-head', [
    'title' => app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact Us',
    'icon' => 'headset',
    'crumbs' => [app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact'],
])
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-badge"><i class="bi bi-headset"></i> {{ app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact Us' }}</div>
            <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'আমাদের সাথে যোগাযোগ করুন' : "We'd love to hear from you" }}</h2>
            <p class="section-subtitle">{{ app()->getLocale() == 'bn' ? 'যেকোনো প্রশ্ন বা সহায়তার জন্য নিচের যেকোনো মাধ্যমে যোগাযোগ করুন' : 'Reach out with any question and our team will get back to you shortly.' }}</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="contact-panel">
                    <div class="section-badge"><i class="bi bi-chat-dots"></i> {{ app()->getLocale() == 'bn' ? 'যোগাযোগের তথ্য' : 'Contact Info' }}</div>
                    <h3>{{ app()->getLocale() == 'bn' ? 'সরাসরি কথা বলুন' : "Let's talk directly" }}</h3>
                    <p>{{ app()->getLocale() == 'bn' ? 'ফোন, হোয়াটসঅ্যাপ বা সরাসরি ঠিকানায় আমাদের সাথে যোগাযোগ করুন।' : 'Call, message on WhatsApp, or drop by — whichever works best for you.' }}</p>

                    <ul class="contact-info-list">
                        <li>
                            <span class="icon-circle"><i class="bi bi-telephone"></i></span>
                            <div>
                                <span class="label">{{ app()->getLocale() == 'bn' ? 'ফোন' : 'Phone' }}</span>
                                <a href="tel:{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}">{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}</a>
                            </div>
                        </li>
                        <li>
                            <span class="icon-circle"><i class="bi bi-whatsapp"></i></span>
                            <div>
                                <span class="label">WhatsApp</span>
                                <a href="{{ \App\Support\Whatsapp::shopUrl() ?: '#' }}" target="_blank" rel="noopener">{{ \App\Support\ChatSettings::whatsappNumber() ?: 'Not set' }}</a>
                            </div>
                        </li>
                        <li>
                            <span class="icon-circle"><i class="bi bi-geo-alt"></i></span>
                            <div>
                                <span class="label">{{ app()->getLocale() == 'bn' ? 'ঠিকানা' : 'Address' }}</span>
                                <span class="value">{{ \App\Models\Setting::get('address', 'Laksham, cumilla') }}</span>
                            </div>
                        </li>
                    </ul>

                    @php
                        $socials = array_filter([
                            'facebook' => \App\Models\Setting::get('facebook'),
                            'instagram' => \App\Models\Setting::get('instagram'),
                            'tiktok' => \App\Models\Setting::get('tiktok'),
                            'youtube' => \App\Models\Setting::get('youtube'),
                        ]);
                    @endphp
                    @if ($socials)
                        <div class="footer-social">
                            @foreach ($socials as $network => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                                    <i class="bi bi-{{ $network }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="contact-form-card">
                    <h4 style="color: var(--primary-dark);" class="mb-1">{{ app()->getLocale() == 'bn' ? 'বার্তা পাঠান' : 'Send us a message' }}</h4>
                    <p class="text-muted mb-4">{{ app()->getLocale() == 'bn' ? 'নিচের ফর্মটি পূরণ করুন, আমরা হোয়াটসঅ্যাপে আপনার সাথে যোগাযোগ করব।' : "Fill in the form below and we'll follow up on WhatsApp." }}</p>
                    <form action="{{ route('contact.store') }}" method="POST">
                        @csrf
                        {{-- Honeypot: hidden from a human, so anything in it came from a bot. --}}
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ app()->getLocale() == 'bn' ? 'নাম' : 'Name' }}</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="{{ app()->getLocale() == 'bn' ? 'আপনার নাম' : 'Your name' }}">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ app()->getLocale() == 'bn' ? 'ফোন' : 'Phone' }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="01XXXXXXXXX">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ app()->getLocale() == 'bn' ? 'বার্তা' : 'Message' }}</label>
                                <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" placeholder="{{ app()->getLocale() == 'bn' ? 'আপনার বার্তা...' : 'Your message...' }}">{{ old('message') }}</textarea>
                                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-3">
                                <button type="submit" class="btn-primary-custom">
                                    <i class="bi bi-send"></i> {{ app()->getLocale() == 'bn' ? 'বার্তা পাঠান' : 'Send Message' }}
                                </button>
                                @if($sendUrl = \App\Support\Whatsapp::shopUrl())
                                <a href="{{ $sendUrl }}" target="_blank" rel="noopener" class="btn-whatsapp">
                                    <i class="bi bi-whatsapp"></i> {{ app()->getLocale() == 'bn' ? 'হোয়াটসঅ্যাপে পাঠান' : 'Send via WhatsApp' }}
                                </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
