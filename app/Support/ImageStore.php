<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One way in and one way out for every uploaded picture.
 *
 * Files land in public/uploads/<folder>/<year>/<month>/<name>.webp. WebP is
 * typically a third the size of the JPEG or PNG that was uploaded, which is
 * most of what makes the shop feel quick on a phone.
 *
 *   $path = ImageStore::put($request->file('image'), 'categories');
 *   // 'uploads/categories/2026/08/mango-a1b2c3d4.webp'
 *
 * The stored value is always relative to public/, so ImageStore::url() is all
 * a view needs. The disk is rooted at public/ for that reason; everything this
 * class writes is under uploads/.
 */
class ImageStore
{
    /** Anything wider than this is scaled down; nothing on the shop needs more. */
    public const MAX_WIDTH = 1600;

    public const QUALITY = 82;

    public const DISK = 'uploads';

    public const ROOT = 'uploads';

    /**
     * Stores an upload as WebP and returns its path relative to public/.
     *
     * An image GD cannot read — an unusual format, a corrupt file — is kept in
     * its original form rather than rejected, so an upload never silently
     * disappears.
     */
    public static function put(UploadedFile $file, string $folder): string
    {
        $target = self::path($folder, self::basename($file));
        $webp = self::convert($file->getRealPath());

        if ($webp !== null) {
            self::disk()->put($target, $webp);

            return $target;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fallback = substr($target, 0, -strlen('webp')).$extension;

        self::disk()->put($fallback, $file->get());

        return $fallback;
    }

    /**
     * Converts an image that already exists somewhere else — used when
     * migrating the pictures uploaded before this class existed. The caller
     * reads the bytes, so the source can be any disk.
     *
     * @return string|null the new path, or null when the bytes are not an image
     */
    public static function adopt(string $contents, string $folder, ?string $name = null): ?string
    {
        $webp = self::encode($contents);

        if ($webp === null) {
            return null;
        }

        $target = self::path($folder, Str::limit(Str::slug($name ?: 'image') ?: 'image', 40, '').'-'.Str::random(8).'.webp');

        self::disk()->put($target, $webp);

        return $target;
    }

    /** Public URL for a stored path, whatever era it comes from. */
    public static function url(?string $path, string $fallback = 'assets/img/placeholder.png'): string
    {
        if (blank($path)) {
            return asset($fallback);
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Written by this class.
        if (self::isStored($path)) {
            return asset($path);
        }

        // Written before it existed: storage/app/public, exposed at /storage.
        if (str_contains($path, '/')) {
            return asset('storage/'.$path);
        }

        return asset($fallback);
    }

    /**
     * Copies a stored file and returns the new path.
     *
     * Two records must never share one path: deleting either would take the
     * picture out from under the other. Anything this class did not write — an
     * external URL, a missing file — is handed back unchanged, because there is
     * nothing to copy and nothing that will be deleted either.
     */
    public static function duplicate(?string $path, string $folder): ?string
    {
        if (! self::exists((string) $path)) {
            return $path;
        }

        $stem = Str::limit(pathinfo($path, PATHINFO_FILENAME), 40, '') ?: 'image';
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
        $target = self::path($folder, $stem.'-'.Str::random(8).'.'.$extension);

        self::disk()->put($target, self::disk()->get($path));

        return $target;
    }

    /** Removes a file this class wrote. Anything else is left alone. */
    public static function delete(?string $path): void
    {
        if (self::isStored($path)) {
            self::disk()->delete($path);
        }
    }

    public static function isStored(?string $path): bool
    {
        return filled($path) && str_starts_with($path, self::ROOT.'/');
    }

    public static function exists(string $path): bool
    {
        return self::isStored($path) && self::disk()->exists($path);
    }

    /* ------------------------------------------------------------ internals */

    private static function disk()
    {
        return Storage::disk(self::DISK);
    }

    private static function path(string $folder, string $name): string
    {
        // Dated folders keep any one directory small enough to list.
        return self::ROOT.'/'.trim($folder, '/').'/'.date('Y/m').'/'.$name;
    }

    private static function basename(UploadedFile $file): string
    {
        $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';

        return Str::limit($stem, 40, '').'-'.Str::random(8).'.webp';
    }

    /** @return string|null the WebP bytes, or null when GD cannot read the file */
    private static function convert(string $source): ?string
    {
        if (! is_file($source)) {
            return null;
        }

        $contents = @file_get_contents($source);

        return $contents === false ? null : self::encode($contents);
    }

    /** @return string|null the WebP bytes, or null when the bytes are not an image */
    private static function encode(string $contents): ?string
    {
        $image = @imagecreatefromstring($contents);

        if (! $image) {
            return null;
        }

        $image = self::downscale($image);

        // A PNG's transparency has to survive the trip.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, self::QUALITY);
        $bytes = (string) ob_get_clean();

        return $ok && $bytes !== '' ? $bytes : null;
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private static function downscale($image)
    {
        $width = imagesx($image);

        if ($width <= self::MAX_WIDTH) {
            return $image;
        }

        $height = (int) round(imagesy($image) * (self::MAX_WIDTH / $width));
        $resized = imagescale($image, self::MAX_WIDTH, $height);

        return $resized === false ? $image : $resized;
    }
}
