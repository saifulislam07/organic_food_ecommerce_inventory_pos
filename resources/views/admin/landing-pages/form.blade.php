@extends('admin.layouts.app')
@section('page_title', $page ? 'ল্যান্ডিং পেজ এডিট' : 'নতুন ল্যান্ডিং পেজ')

@php
    /** datetime-local wants Y-m-dTH:i and nothing else. */
    $dt = fn ($value) => $value?->format('Y-m-d\TH:i');

    $maxKb = \App\Http\Controllers\Admin\AdminLandingPageController::MAX_IMAGE_KB;

    // PHP throws an oversized upload away before Laravel ever sees it, and the
    // form then looks as though nothing happened. Worth saying out loud.
    $serverKb = (int) (min(
        ini_parse_quantity(ini_get('upload_max_filesize') ?: '2M'),
        ini_parse_quantity(ini_get('post_max_size') ?: '8M')
    ) / 1024);

    $uploadLimitNote = $serverKb < $maxKb
        ? 'এই সার্ভার সর্বোচ্চ '.round($serverKb / 1024, 1).' MB নেয় — এর চেয়ে বড় ছবি নিঃশব্দে বাদ পড়বে। php.ini-তে upload_max_filesize ও post_max_size বাড়ান।'
        : null;
@endphp

@section('content')
<form action="{{ $page ? route('admin.landing-pages.update', $page) : route('admin.landing-pages.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($page) @method('PUT') @endif

    @if($page)
        <div class="alert alert-light border d-flex flex-wrap align-items-center gap-3 mb-4">
            <div>
                <div class="small text-muted">পেজের লিংক</div>
                <code>{{ $page->url() }}</code>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="{{ $page->url() }}">
                    <i class="bi bi-link-45deg"></i> লিংক কপি
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-copy="{{ $page->adUrl() }}">
                    <i class="bi bi-megaphone"></i> অ্যাড লিংক কপি (UTM সহ)
                </button>
                <a href="{{ $page->url() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-box-arrow-up-right"></i> দেখুন
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Hero --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-megaphone"></i> উপরের অংশ</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold">অ্যাডমিনের জন্য নাম *</label>
                        <input type="text" name="internal_name"
                               class="form-control @error('internal_name') is-invalid @enderror"
                               value="{{ old('internal_name', $page->internal_name ?? '') }}"
                               placeholder="যেমন: ঈদ আম কম্বো — রিটার্গেটিং" required>
                        @error('internal_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">শুধু এই লিস্টে দেখা যাবে, ক্রেতা দেখবে না।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">হেডলাইন *</label>
                        <input type="text" name="headline"
                               class="form-control form-control-lg @error('headline') is-invalid @enderror"
                               value="{{ old('headline', $page->headline ?? '') }}"
                               placeholder="খাঁটি হিমসাগর আম — গাছপাকা, ফরমালিন মুক্ত" required>
                        @error('headline') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">সাব-হেডলাইন</label>
                            <input type="text" name="subheadline" class="form-control"
                                   value="{{ old('subheadline', $page->subheadline ?? '') }}"
                                   placeholder="সারা দেশে ক্যাশ অন ডেলিভারি">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">অফার ব্যাজ</label>
                            <input type="text" name="badge_text" class="form-control" maxlength="100"
                                   value="{{ old('badge_text', $page->badge_text ?? '') }}"
                                   placeholder="৪০% ছাড়">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">ভিডিও লিংক</label>
                            <input type="url" name="video_url"
                                   class="form-control @error('video_url') is-invalid @enderror"
                                   value="{{ old('video_url', $page->video_url ?? '') }}"
                                   placeholder="https://www.youtube.com/watch?v=…">
                            @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">YouTube বা Facebook ভিডিও। খালি রাখলে হিরো ছবি দেখাবে।</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- What is for sale --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> কী বিক্রি হবে</h6>
                    <div
                        data-vue="LandingItems"
                        data-props="{{ json_encode([
                            'rows' => $itemRows,
                            'variants' => $variantOptions,
                            'modes' => \App\Models\LandingPage::MODES,
                            'mode' => old('selection_mode', $page->selection_mode ?? 'single'),
                            'bundlePrice' => old('bundle_price', $page->bundle_price ?? null),
                            'errors' => $errors->toArray(),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>
                </div>
            </div>

            {{-- Content blocks --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-layout-text-window"></i> পেজের কনটেন্ট</h6>
                    <div
                        data-vue="LandingContentBlocks"
                        data-props="{{ json_encode([
                            'blocks' => \App\Models\LandingPage::BLOCKS,
                            'sections' => old('sections', $page?->enabledSections() ?? \App\Models\LandingPage::DEFAULT_SECTIONS),
                            'features' => old('features', $page?->featureList() ?? []),
                            'faqs' => old('faqs', $page?->faqList() ?? []),
                            'reviews' => old('reviews', $page?->reviewList() ?? []),
                            'specs' => old('specs', $page?->specList() ?? []),
                            'defaults' => $categoryDefaults,
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">বিস্তারিত বর্ণনা</label>
                        <textarea name="body" data-editor="basic" data-editor-height="280"
                                  class="form-control" rows="5">{{ old('body', $page->body ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Delivery, payment, form --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-truck"></i> ডেলিভারি, পেমেন্ট ও অর্ডার ফর্ম</h6>

                    @php
                        $deliveryMode = old('delivery_mode', $page->delivery_mode ?? 'global');
                        $paymentMode = old('payment_mode', $page->payment_mode ?? 'cod');
                    @endphp

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ডেলিভারি চার্জ</label>
                            <select name="delivery_mode" class="form-select">
                                <option value="global" @selected($deliveryMode === 'global')>দোকানের সাধারণ চার্জ</option>
                                <option value="custom" @selected($deliveryMode === 'custom')>এই পেজের নিজস্ব চার্জ</option>
                                <option value="free" @selected($deliveryMode === 'free')>ফ্রি ডেলিভারি</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ঢাকার ভেতরে (৳)</label>
                            <input type="number" name="delivery_inside" min="0" step="1" class="form-control"
                                   value="{{ old('delivery_inside', $page->delivery_inside ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ঢাকার বাইরে (৳)</label>
                            <input type="number" name="delivery_outside" min="0" step="1" class="form-control"
                                   value="{{ old('delivery_outside', $page->delivery_outside ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">পেমেন্ট</label>
                            <select name="payment_mode" class="form-select">
                                <option value="cod" @selected($paymentMode === 'cod')>ক্যাশ অন ডেলিভারি</option>
                                <option value="advance" @selected($paymentMode === 'advance')>অগ্রিম লাগবে (নোট দেখাবে)</option>
                            </select>
                            <div class="form-text">অগ্রিম টাকা এখানে নেওয়া হয় না — শুধু নির্দেশনা দেখানো হয়।</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">অগ্রিমের পরিমাণ (৳)</label>
                            <input type="number" name="advance_amount" min="0" step="1" class="form-control"
                                   value="{{ old('advance_amount', $page->advance_amount ?? '') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">পেমেন্ট নির্দেশনা</label>
                            <textarea name="payment_note" class="form-control" rows="2" maxlength="1000"
                                      placeholder="বিকাশ সেন্ড মানি: 01XXXXXXXXX">{{ old('payment_note', $page->payment_note ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold">ফর্মে যেসব ঘর থাকবে</label>
                            @php
                                $labels = ['address' => 'ঠিকানা', 'area' => 'এলাকা (ঢাকার ভেতরে/বাইরে)', 'note' => 'নোট', 'email' => 'ইমেইল'];
                                $selected = old('form_fields', $page
                                    ? collect(\App\Models\LandingPage::OPTIONAL_FIELDS)->filter(fn ($f) => $page->asksFor($f))->values()->all()
                                    : ['address', 'area', 'note']);
                            @endphp
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                @foreach($labels as $field => $label)
                                    <label class="form-check-label d-flex align-items-center gap-1">
                                        <input type="checkbox" class="form-check-input mt-0" name="form_fields[]"
                                               value="{{ $field }}" @checked(in_array($field, (array) $selected, true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-text">নাম ও মোবাইল নম্বর সবসময় থাকবে।</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">বাটনের লেখা</label>
                            <input type="text" name="cta_text" class="form-control" maxlength="100"
                                   value="{{ old('cta_text', $page->cta_text ?? '') }}"
                                   placeholder="অর্ডার করুন">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Thank you --}}
            <div class="card admin-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-check2-circle"></i> অর্ডারের পরের পেজ</h6>
                    <div class="mb-3">
                        <label class="form-label">হেডলাইন</label>
                        <input type="text" name="thankyou_headline" class="form-control"
                               value="{{ old('thankyou_headline', $page->thankyou_headline ?? '') }}"
                               placeholder="অর্ডার সফল হয়েছে!">
                    </div>
                    <div>
                        <label class="form-label">বার্তা</label>
                        <textarea name="thankyou_body" class="form-control" rows="2" maxlength="2000"
                                  placeholder="আমাদের প্রতিনিধি শীঘ্রই ফোন দেবেন।">{{ old('thankyou_body', $page->thankyou_body ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if($page && ($stats ?? null))
                <div class="card admin-card mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-graph-up"></i> এই পেজের ফলাফল</h6>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ভিউ</span>
                            <strong>{{ number_format($stats['views']) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">অর্ডার</span>
                            <strong>{{ number_format($stats['orders']) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">কনভার্শন</span>
                            <strong>{{ $stats['conversion'] === null ? '—' : $stats['conversion'].'%' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="text-muted">বিক্রি</span>
                            <strong style="color: var(--primary);">৳{{ number_format($stats['revenue']) }}</strong>
                        </div>

                        @if($stats['campaigns']->isNotEmpty())
                            <div class="mt-3 pt-3 border-top">
                                <div class="small fw-bold text-muted mb-2">ক্যাম্পেইন অনুযায়ী</div>
                                @foreach($stats['campaigns'] as $campaign)
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-truncate me-2">{{ $campaign->utm_campaign }}</span>
                                        <span class="text-nowrap">
                                            {{ $campaign->orders }} টি ·
                                            <strong>৳{{ number_format((float) $campaign->revenue) }}</strong>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($stats['orders'] > 0)
                            <p class="form-text mt-3 mb-0">
                                অর্ডারগুলোতে কোনো ক্যাম্পেইন ট্যাগ নেই — উপরের
                                <strong>অ্যাড লিংক কপি</strong> বাটনের লিংকটি বুস্টে ব্যবহার করলে এখানে ভাগ করে দেখা যাবে।
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Category and theme --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-palette"></i> ক্যাটাগরি ও থিম</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ক্যাটাগরি</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror"
                                data-lp-category>
                            <option value="">— বাছাই করা হয়নি —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                        @selected((string) old('category_id', $page->category_id ?? '') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            পেজের রঙ এখান থেকেই আসে। ক্যাটাগরির থিম ও ডিফল্ট কনটেন্ট ঠিক করুন
                            <a href="{{ route('admin.categories.index') }}" target="_blank" rel="noopener">ক্যাটাগরি</a> পাতা থেকে।
                        </div>

                        {{-- Never automatic: the same click on a page whose copy
                             is already written would throw the copy away. --}}
                        <button type="button" class="btn btn-sm btn-outline-success mt-2 w-100"
                                data-lp-apply-defaults hidden>
                            <i class="bi bi-magic"></i> এই ক্যাটাগরির ডিফল্ট কনটেন্ট বসান
                        </button>
                        <div class="form-text" data-lp-apply-note hidden>
                            নিচের ব্লক, বুলেট পয়েন্ট, স্পেসিফিকেশন ও প্রশ্নগুলো ক্যাটাগরির ডিফল্ট দিয়ে বদলে যাবে।
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">থিম</label>
                        <select name="theme" class="form-select @error('theme') is-invalid @enderror" data-lp-theme>
                            <option value="">ক্যাটাগরি অনুযায়ী</option>
                            @foreach(\App\Models\LandingPage::THEMES as $key => $label)
                                <option value="{{ $key }}" @selected(old('theme', $page->theme ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('theme') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">শুধু এই ক্যাম্পেইনে অন্য রঙ চাইলে বেছে নিন।</div>
                    </div>

                    {{-- The colours themselves, read straight out of
                         landing-themes.css, so this can never drift from what
                         the live page renders. --}}
                    <div class="lp-swatch d-flex align-items-center gap-2 border rounded p-2"
                         data-lp-preview
                         data-categories="{{ json_encode($categories->pluck('theme', 'id'), JSON_UNESCAPED_UNICODE) }}"
                         data-has-defaults="{{ json_encode(array_keys($categoryDefaults)) }}"
                         data-ctas="{{ json_encode(array_filter(array_map(fn ($d) => $d['cta_text'], $categoryDefaults))) }}"
                         data-labels="{{ json_encode(\App\Models\LandingPage::THEMES, JSON_UNESCAPED_UNICODE) }}">
                        <span class="lp-swatch-dot" style="background: var(--primary);"></span>
                        <span class="lp-swatch-dot" style="background: var(--accent);"></span>
                        <span class="lp-swatch-dot" style="background: var(--cream-dark);"></span>
                        <span class="small text-muted ms-1" data-lp-preview-name></span>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-toggle-on"></i> স্ট্যাটাস ও সময়</h6>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $page->is_active ?? false))>
                        <label class="form-check-label fw-bold">লাইভ করুন</label>
                        <div class="form-text">বন্ধ থাকলে শুধু আপনি প্রিভিউ দেখতে পাবেন।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL (স্লাগ)</label>
                        <div class="input-group">
                            <span class="input-group-text">/{{ config('landing.prefix', 'lp') }}/</span>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $page->slug ?? '') }}" placeholder="eid-mango-combo">
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">খালি রাখলে নাম থেকে তৈরি হবে। লাইভ পেজের URL বদলালে পুরোনো অ্যাডের লিংক ভেঙে যাবে।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">শুরু</label>
                        <input type="datetime-local" name="starts_at" class="form-control"
                               value="{{ old('starts_at', $dt($page?->starts_at)) }}">
                    </div>

                    <div>
                        <label class="form-label">শেষ</label>
                        <input type="datetime-local" name="ends_at"
                               class="form-control @error('ends_at') is-invalid @enderror"
                               value="{{ old('ends_at', $dt($page?->ends_at)) }}">
                        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">সময় শেষ হলে পেজ নিজে থেকেই অর্ডার নেওয়া বন্ধ করবে।</div>
                    </div>
                </div>
            </div>

            {{-- Hero image --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-image"></i> হিরো ছবি</h6>
                    @if($page?->heroImageUrl())
                        <img src="{{ $page->heroImageUrl() }}" alt="" class="img-fluid rounded border mb-2">
                    @endif
                    <input type="file" name="hero_image" accept="image/*"
                           class="form-control @error('hero_image') is-invalid @enderror">
                    @error('hero_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">
                        মোবাইলে সবচেয়ে আগে এটাই চোখে পড়ে। JPG / PNG / WebP,
                        সর্বোচ্চ {{ round($maxKb / 1024) }} MB — আপলোডের পর নিজে থেকেই ছোট হয়ে WebP হয়ে যাবে।
                    </div>
                    @if($uploadLimitNote)
                        <div class="alert alert-warning py-2 small mt-2 mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $uploadLimitNote }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Urgency --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-hourglass-split"></i> তাড়া তৈরি</h6>
                    <div class="mb-3">
                        <label class="form-label">কাউন্টডাউন শেষ হবে</label>
                        <input type="datetime-local" name="countdown_ends_at" class="form-control"
                               value="{{ old('countdown_ends_at', $dt($page?->countdown_ends_at)) }}">
                    </div>
                    <div>
                        <label class="form-label">স্টক নোট</label>
                        <input type="text" name="stock_note" class="form-control" maxlength="255"
                               value="{{ old('stock_note', $page->stock_note ?? '') }}"
                               placeholder="মাত্র ১২ পিস বাকি">
                    </div>
                </div>
            </div>

            {{-- Tracking & sharing --}}
            <div class="card admin-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow"></i> ট্র্যাকিং ও শেয়ার</h6>

                    <div class="mb-3">
                        <label class="form-label">Meta Pixel ID</label>
                        <input type="text" name="pixel_id" class="form-control @error('pixel_id') is-invalid @enderror"
                               value="{{ old('pixel_id', $page->pixel_id ?? '') }}"
                               placeholder="{{ \App\Support\SeoSettings::facebookPixelId() ?: '123456789012345' }}">
                        @error('pixel_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            খালি রাখলে সাইটের সাধারণ পিক্সেল ব্যবহার হবে
                            (<a href="{{ route('admin.settings.seo.edit') }}">SEO সেটিংস</a>)।
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">শেয়ার ছবি</label>
                        @if($page?->og_image)
                            <img src="{{ \App\Support\ImageStore::url($page->og_image) }}" alt=""
                                 class="img-fluid rounded border mb-2">
                        @endif
                        <input type="file" name="og_image" accept="image/*" class="form-control">
                        <div class="form-text">খালি রাখলে হিরো ছবিই শেয়ারে যাবে।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" maxlength="70" class="form-control"
                               value="{{ old('meta_title', $page->meta_title ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" maxlength="180" rows="2"
                                  class="form-control">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input type="hidden" name="noindex" value="0">
                        <input class="form-check-input" type="checkbox" name="noindex" value="1"
                               @checked(old('noindex', $page->noindex ?? true))>
                        <label class="form-check-label">গুগলে দেখাবে না (noindex)</label>
                        <div class="form-text">
                            অ্যাড পেজ সার্চে এলে আসল প্রোডাক্ট পেজের সাথে প্রতিযোগিতা করে। সাধারণত চালু রাখাই ভালো।
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-check-circle"></i> {{ $page ? 'সংরক্ষণ করুন' : 'পেজ তৈরি করুন' }}
            </button>

            @if($page)
                <a href="{{ route('admin.landing-pages.index') }}" class="btn btn-link w-100 mt-2">তালিকায় ফিরে যান</a>
            @endif
        </div>
    </div>
</form>
@endsection

{{-- The theme classes are pure custom-property declarations, so loading them
     here colours the swatch and touches nothing else on the admin screen. --}}
@push('styles')
    <link href="{{ asset('css/landing-themes.css') }}" rel="stylesheet">
    <style>
        .lp-swatch-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .12);
            flex: none;
        }
    </style>
@endpush

@push('scripts')
<script>
/*
    Keeps the swatch honest about what the page will actually look like: the
    theme the page names, or failing that the one its category carries, or the
    brand. The same three-step fallback LandingPage::themeKey() does — worth
    the duplication, because an admin who cannot see the answer until they save
    picks a category and hopes.
*/
(function () {
    const preview = document.querySelector('[data-lp-preview]');

    if (! preview) {
        return;
    }

    const categorySelect = document.querySelector('[data-lp-category]');
    const themeSelect = document.querySelector('[data-lp-theme]');
    const name = preview.querySelector('[data-lp-preview-name]');
    const inherit = themeSelect.querySelector('option[value=""]');

    const categoryThemes = JSON.parse(preview.dataset.categories || '{}');
    const labels = JSON.parse(preview.dataset.labels || '{}');

    function paint() {
        const inherited = categoryThemes[categorySelect.value] || 'default';
        const key = themeSelect.value || inherited;

        inherit.textContent = 'ক্যাটাগরি অনুযায়ী — ' + (labels[inherited] || labels.default);

        preview.className = preview.className.replace(/\blp-theme-\S+/g, '').trim()
            + ' lp-theme-' + key;
        name.textContent = labels[key] || labels.default;
    }

    /*
        The prefill button, which only exists for a category that actually has
        a starting draft. It hands the id to the content island and stops
        there — what gets replaced is that component's business, and it will
        not act unless a human clicked here.
    */
    const applyButton = document.querySelector('[data-lp-apply-defaults]');
    const applyNote = document.querySelector('[data-lp-apply-note]');
    const withDefaults = new Set(JSON.parse(preview.dataset.hasDefaults || '[]').map(String));

    function offerDefaults() {
        const available = withDefaults.has(categorySelect.value);

        applyButton.hidden = ! available;
        applyNote.hidden = ! available;
    }

    applyButton.addEventListener('click', function () {
        document.dispatchEvent(new CustomEvent('landing:apply-defaults', {
            detail: { categoryId: categorySelect.value },
        }));

        const ctaText = document.querySelector('[name="cta_text"]');
        const cta = (JSON.parse(preview.dataset.ctas || '{}'))[categorySelect.value];

        if (ctaText && cta) {
            ctaText.value = cta;
        }

        applyButton.disabled = true;
        applyButton.innerHTML = '<i class="bi bi-check2"></i> বসানো হয়েছে';
    });

    categorySelect.addEventListener('change', function () {
        paint();
        offerDefaults();

        applyButton.disabled = false;
        applyButton.innerHTML = '<i class="bi bi-magic"></i> এই ক্যাটাগরির ডিফল্ট কনটেন্ট বসান';
    });

    themeSelect.addEventListener('change', paint);

    paint();
    offerDefaults();
})();
</script>
@endpush
