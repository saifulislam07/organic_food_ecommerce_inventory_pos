@extends('layouts.frontend')
@section('title', 'Contact Us – Mango Hut')
@section('content')
<div class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><span>/</span></li>
            <li>Contact</li>
        </ul>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card admin-card p-4 text-center h-100">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 16px;">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <h5>Phone</h5>
                    <p class="text-muted">01716-952365</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card admin-card p-4 text-center h-100">
                    <div style="font-size: 2.5rem; color: #25d366; margin-bottom: 16px;">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <h5>WhatsApp</h5>
                    <a href="https://wa.me/8801716952365" target="_blank" class="text-muted">01716-952365</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card admin-card p-4 text-center h-100">
                    <div style="font-size: 2.5rem; color: var(--accent); margin-bottom: 16px;">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h5>Address</h5>
                    <p class="text-muted">চাঁপাই নবাবগঞ্জ, রাজশাহী, বাংলাদেশ</p>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="card admin-card p-4">
                    <h4 style="color: var(--primary-dark);" class="mb-4"><i class="bi bi-envelope"></i> Send us a message</h4>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name</label>
                                <input type="text" class="form-control" placeholder="Your name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" class="form-control" placeholder="01XXXXXXXXX">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Message</label>
                                <textarea class="form-control" rows="4" placeholder="Your message..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn-primary-custom" onclick="window.open('https://wa.me/8801716952365', '_blank')">
                                    <i class="bi bi-whatsapp"></i> Send via WhatsApp
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
