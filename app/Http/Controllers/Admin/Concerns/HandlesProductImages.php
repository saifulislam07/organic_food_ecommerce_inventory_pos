<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * A product carries up to Product::MAX_IMAGES photos. One of them is the
 * thumbnail, and that path is mirrored onto products.image so the fourteen
 * places already reading $product->image_url keep working untouched.
 */
trait HandlesProductImages
{
    protected function syncProductImages(Request $request, Product $product): void
    {
        $this->deleteRequestedImages($request, $product);
        $this->storeNewImages($request, $product);
        $this->applyThumbnail($request, $product);
    }

    /** @throws ValidationException when the upload would exceed the limit */
    protected function guardImageCount(Request $request, ?Product $product = null): void
    {
        $existing = $product ? $product->images()->count() : 0;
        $removing = count($request->input('remove_images', []));
        $adding = count(array_filter($request->file('images', [])));

        if ($existing - $removing + $adding > Product::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'A product can have at most '.Product::MAX_IMAGES.' images.',
            ]);
        }
    }

    private function deleteRequestedImages(Request $request, Product $product): void
    {
        $ids = $request->input('remove_images', []);

        if (! $ids) {
            return;
        }

        $product->images()->whereIn('id', $ids)->get()->each(function (ProductImage $image) {
            if (str_starts_with($image->path, 'products/')) {
                Storage::disk('public')->delete($image->path);
            }

            $image->delete();
        });
    }

    private function storeNewImages(Request $request, Product $product): void
    {
        $files = array_filter($request->file('images', []));

        if (! $files) {
            return;
        }

        $next = (int) $product->images()->max('sort_order');

        foreach ($files as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort_order' => ++$next,
            ]);
        }
    }

    /**
     * Keeps products.image pointing at a real photo: the chosen one, or the
     * first remaining if the thumbnail was just deleted.
     */
    private function applyThumbnail(Request $request, Product $product): void
    {
        $product->load('images');

        $chosen = $request->input('thumbnail_id');

        $thumbnail = $chosen
            ? $product->images->firstWhere('id', (int) $chosen)
            : null;

        $thumbnail ??= $product->images->firstWhere('path', $product->getRawOriginal('image'));
        $thumbnail ??= $product->images->first();

        $product->update(['image' => $thumbnail?->path]);
    }
}
