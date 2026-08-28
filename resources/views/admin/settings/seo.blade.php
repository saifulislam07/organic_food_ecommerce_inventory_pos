@extends('admin.layouts.app')

@section('title', 'SEO Settings')
@section('page_title', 'SEO, Meta & Analytics')

@section('content')
<form action="{{ route('admin.settings.seo.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-search"></i> Default Meta Tags</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">
                        Used on any page that does not set its own. Product pages already use
                        their own meta title and description.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Title</label>
                        <input type="text" name="seo_meta_title" maxlength="70"
                               class="form-control @error('seo_meta_title') is-invalid @enderror"
                               value="{{ old('seo_meta_title', $seo['seo_meta_title'] ?? '') }}"
                               placeholder="Mango Hut — খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার">
                        @error('seo_meta_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Google shows about 70 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Description</label>
                        <textarea name="seo_meta_description" rows="3" maxlength="180"
                                  class="form-control @error('seo_meta_description') is-invalid @enderror"
                                  placeholder="What the shop sells, in one sentence.">{{ old('seo_meta_description', $seo['seo_meta_description'] ?? '') }}</textarea>
                        @error('seo_meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">About 180 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keywords</label>
                        <input type="text" name="seo_meta_keywords"
                               class="form-control @error('seo_meta_keywords') is-invalid @enderror"
                               value="{{ old('seo_meta_keywords', $seo['seo_meta_keywords'] ?? '') }}"
                               placeholder="আম, খেজুর গুড়, ঘি, সরিষার তেল">
                        @error('seo_meta_keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Comma separated. Google ignores these, but some other engines still read them.</div>
                    </div>

                    <div>
                        <label class="form-label fw-bold">Search Engine Visibility</label>
                        @php $robots = old('seo_robots', $seo['seo_robots'] ?? 'index, follow'); @endphp
                        <select name="seo_robots" class="form-select">
                            <option value="index, follow" @selected($robots === 'index, follow')>
                                Visible — let search engines index the site
                            </option>
                            <option value="noindex, nofollow" @selected($robots === 'noindex, nofollow')>
                                Hidden — ask search engines to stay away
                            </option>
                        </select>
                        <div class="form-text">Set to hidden while the shop is still being built.</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up"></i> Google</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Analytics Measurement ID</label>
                        <input type="text" name="seo_google_analytics"
                               class="form-control @error('seo_google_analytics') is-invalid @enderror"
                               value="{{ old('seo_google_analytics', $seo['seo_google_analytics'] ?? '') }}"
                               placeholder="G-XXXXXXXXXX">
                        @error('seo_google_analytics') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            From Google Analytics → Admin → Data Streams. Leave blank to load no tracking script at all.
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-bold">Search Console Verification</label>
                        <input type="text" name="seo_google_site_verification"
                               class="form-control @error('seo_google_site_verification') is-invalid @enderror"
                               value="{{ old('seo_google_site_verification', $seo['seo_google_site_verification'] ?? '') }}"
                               placeholder="The content value of the meta tag Google gives you">
                        @error('seo_google_site_verification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-image"></i> Share Image</h5>
                </div>
                <div class="card-body p-4">
                    @if($ogImageUrl)
                        <img src="{{ $ogImageUrl }}" alt="Share image" class="img-fluid rounded border mb-3">
                    @endif
                    <input type="file" name="og_image" accept="image/*"
                           class="form-control @error('og_image') is-invalid @enderror">
                    @error('og_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">
                        Shown when a link is shared on Facebook, WhatsApp or Messenger.
                        1200 × 630 works best. Product pages use their own photo instead.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-dark">Sitemap</h6>
                    <p class="text-muted small mb-2">
                        Generated automatically from your live products, categories and pages.
                    </p>
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-box-arrow-up-right"></i> View sitemap.xml
                    </a>
                    <p class="text-muted small mb-0 mt-3">
                        Submit that URL in Google Search Console once verification is done.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> Save SEO Settings
        </button>
    </div>
</form>
@endsection
