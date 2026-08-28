<?php

namespace App\Models\Concerns;

use App\Support\ImageStore;
use Illuminate\Support\Facades\Storage;

/**
 * Deleting a row deletes the picture that came with it.
 *
 * Two eras of path exist: new uploads under public/uploads, and older ones on
 * the public disk under a folder prefix. Anything that matches neither is a
 * shipped asset in public/assets and must never be deleted.
 */
trait CleansUpImages
{
    protected static function deleteUploadedImage(?string $path, string $legacyPrefix): void
    {
        if (blank($path)) {
            return;
        }

        if (ImageStore::isStored($path)) {
            ImageStore::delete($path);

            return;
        }

        if (str_starts_with($path, $legacyPrefix)) {
            Storage::disk('public')->delete($path);
        }
    }
}
