@extends('admin.layouts.app')

@section('title', 'Chat Settings')
@section('page_title', 'WhatsApp & Messenger')

@php
    $position = old('chat_position', $chat['chat_position'] ?? 'right');
    $waOn = old('chat_whatsapp_enabled', ($chat['chat_whatsapp_enabled'] ?? null) === null ? (bool) $whatsappUrl : $chat['chat_whatsapp_enabled']);
    $fbOn = old('chat_messenger_enabled', $chat['chat_messenger_enabled'] ?? '');
@endphp

@section('content')
<form action="{{ route('admin.settings.chat.update') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">

            {{-- WhatsApp --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-whatsapp text-success"></i> WhatsApp</h5>
                    @if($whatsappUrl)
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> Showing</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Hidden</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" name="chat_whatsapp_enabled" value="1" id="waOn"
                               class="form-check-input" @checked($waOn)>
                        <label class="form-check-label fw-bold" for="waOn">Show the WhatsApp button on the site</label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">WhatsApp number</label>
                            <input type="text" name="chat_whatsapp_number"
                                   class="form-control @error('chat_whatsapp_number') is-invalid @enderror"
                                   value="{{ old('chat_whatsapp_number', $chat['chat_whatsapp_number'] ?? '') }}"
                                   placeholder="01716-952365">
                            @error('chat_whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                Leave blank to use the shop number from Site Settings
                                ({{ $whatsappNumber ?: 'not set yet' }}).
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Where the buttons sit</label>
                            <select name="chat_position" class="form-select">
                                <option value="right" @selected($position === 'right')>Bottom right</option>
                                <option value="left" @selected($position === 'left')>Bottom left</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">First message (English)</label>
                            <textarea name="chat_whatsapp_message_en" rows="2" class="form-control"
                                      placeholder="Hello! I want to order from your website.">{{ old('chat_whatsapp_message_en', $chat['chat_whatsapp_message_en'] ?? '') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">First message (বাংলা)</label>
                            <textarea name="chat_whatsapp_message_bn" rows="2" class="form-control"
                                      placeholder="হ্যালো! আমি আপনার ওয়েবসাইট থেকে অর্ডার করতে চাই।">{{ old('chat_whatsapp_message_bn', $chat['chat_whatsapp_message_bn'] ?? '') }}</textarea>
                            <div class="form-text">Typed into the customer's message box for them. Blank means an empty chat.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Messenger --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-messenger text-primary"></i> Messenger</h5>
                    @if($messengerUrl)
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> Showing</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Hidden</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" name="chat_messenger_enabled" value="1" id="fbOn"
                               class="form-check-input" @checked($fbOn)>
                        <label class="form-check-label fw-bold" for="fbOn">Show the Messenger button on the site</label>
                    </div>

                    <label class="form-label fw-bold">Facebook page</label>
                    <input type="text" name="chat_messenger_id"
                           class="form-control @error('chat_messenger_id') is-invalid @enderror"
                           value="{{ old('chat_messenger_id', $chat['chat_messenger_id'] ?? '') }}"
                           placeholder="mangohut.bd">
                    @error('chat_messenger_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">
                        The page username, its numeric ID, or the full page link — all three work.
                        Find it in your page's address bar: <code>facebook.com/<strong>mangohut.bd</strong></code>.
                    </div>
                </div>
            </div>

            <button class="btn btn-primary px-5 mt-4"><i class="bi bi-save"></i> Save</button>
        </div>

        {{-- Preview --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark">What customers see</h6>
                </div>
                <div class="card-body p-4">
                    @if($whatsappUrl || $messengerUrl)
                        <p class="text-muted small">These open in a new tab. Try them:</p>
                        <div class="d-flex flex-column gap-2">
                            @if($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                                   class="btn btn-sm text-white d-flex align-items-center gap-2" style="background:#25d366;">
                                    <i class="bi bi-whatsapp fs-5"></i> Open WhatsApp chat
                                </a>
                            @endif
                            @if($messengerUrl)
                                <a href="{{ $messengerUrl }}" target="_blank" rel="noopener"
                                   class="btn btn-sm text-white d-flex align-items-center gap-2" style="background:#0084ff;">
                                    <i class="bi bi-messenger fs-5"></i> Open Messenger chat
                                </a>
                            @endif
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            They float at the bottom {{ $chat['chat_position'] ?? 'right' }} of every storefront page.
                        </p>
                    @else
                        <p class="text-muted small mb-0">
                            Nothing is showing on the site yet. Turn on a button above and give it a number or a page.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
