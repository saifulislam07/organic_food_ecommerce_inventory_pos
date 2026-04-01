@extends('admin.layouts.app')
@section('page_title', ($page ?? null) ? 'Edit Page' : 'Create Page')

@section('content')
<div class="card admin-card p-4">
    <form action="{{ ($page ?? null) ? route('admin.pages.update', $page) : route('admin.pages.store') }}" method="POST">
        @csrf
        @if($page ?? null) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label">Title (English) *</label>
                <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror" value="{{ old('title_en', $page->title_en ?? '') }}" required>
                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Title (Bengali) *</label>
                <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror" value="{{ old('title_bn', $page->title_bn ?? '') }}" required>
                @error('title_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-12">
                <label class="form-label">Slug (Optional)</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug ?? '') }}" placeholder="e.g. terms-and-conditions">
                <small class="text-muted">Leave empty to auto-generate from English title.</small>
            </div>

            <div class="col-md-12">
                <label class="form-label">Content (English) *</label>
                <textarea name="content_en" class="form-control @error('content_en') is-invalid @enderror" rows="10" required>{{ old('content_en', $page->content_en ?? '') }}</textarea>
                @error('content_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-12">
                <label class="form-label">Content (Bengali) *</label>
                <textarea name="content_bn" class="form-control @error('content_bn') is-invalid @enderror" rows="10" required>{{ old('content_bn', $page->content_bn ?? '') }}</textarea>
                @error('content_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-save"></i> {{ ($page ?? null) ? 'Update Page' : 'Save Page' }}
                </button>
                <a href="{{ route('admin.pages.index') }}" class="btn btn-light ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
