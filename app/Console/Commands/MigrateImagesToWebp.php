<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Support\ImageStore;
use App\Support\SeoSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves every picture uploaded before ImageStore existed into public/uploads,
 * converted to WebP.
 *
 *   php artisan images:migrate --dry-run   see what would happen
 *   php artisan images:migrate             convert and repoint the database
 *   php artisan images:migrate --prune     …and remove the originals
 *
 * Safe to run twice: anything already under uploads/ is skipped.
 */
class MigrateImagesToWebp extends Command
{
    protected $signature = 'images:migrate
                            {--dry-run : List what would change without touching anything}
                            {--prune : Delete the original file once it has been converted}';

    protected $description = 'Convert existing images to WebP under public/uploads';

    private int $converted = 0;

    private int $skipped = 0;

    private int $missing = 0;

    public function handle(): int
    {
        $this->components->info('Scanning images…');

        $this->migrateProductGalleries();
        $this->migrateProductThumbnails();
        $this->migrateCategories();
        $this->migrateShareImage();
        $this->migrateLogo();

        $this->newLine();
        $this->components->twoColumnDetail('Converted', (string) $this->converted);
        $this->components->twoColumnDetail('Already done', (string) $this->skipped);
        $this->components->twoColumnDetail('Source not found', (string) $this->missing);

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    private function migrateProductGalleries(): void
    {
        ProductImage::query()->with('product')->chunkById(100, function ($images) {
            foreach ($images as $image) {
                $was = $image->path;
                $new = $this->move($was, 'products', $image->product?->name);

                if ($new) {
                    $image->forceFill(['path' => $new])->saveQuietly();

                    // products.image mirrors the chosen gallery photo, and has
                    // to move with it or the thumbnail is converted twice.
                    Product::where('image', $was)->update(['image' => $new]);
                }
            }
        });
    }

    private function migrateProductThumbnails(): void
    {
        Product::query()->chunkById(100, function ($products) {
            foreach ($products as $product) {
                $new = $this->move($product->getRawOriginal('image'), 'products', $product->name);

                if ($new) {
                    $product->forceFill(['image' => $new])->saveQuietly();
                }
            }
        });
    }

    private function migrateCategories(): void
    {
        Category::query()->chunkById(100, function ($categories) {
            foreach ($categories as $category) {
                $new = $this->move($category->getRawOriginal('image'), 'categories', $category->name);

                if ($new) {
                    $category->forceFill(['image' => $new])->saveQuietly();
                }
            }
        });
    }

    private function migrateShareImage(): void
    {
        $new = $this->move(SeoSettings::get('seo_og_image'), 'seo', 'share-image');

        if ($new) {
            Setting::put('seo_og_image', $new);
            SeoSettings::forget();
        }
    }

    private function migrateLogo(): void
    {
        $logo = Setting::where('key', 'logo')->first();

        if (! $logo) {
            return;
        }

        $new = $this->move($logo->value_en, 'settings', 'logo');

        if ($new) {
            Setting::put('logo', $new, 'image');
        }
    }

    /**
     * Converts one stored path.
     *
     * @return string|null the new path, or null when nothing changed
     */
    private function move(?string $path, string $folder, ?string $name = null): ?string
    {
        if (blank($path) || str_starts_with($path, 'http')) {
            return null;
        }

        if (ImageStore::isStored($path)) {
            $this->skipped++;

            return null;
        }

        $source = $this->locate($path);

        if ($source === null) {
            $this->missing++;
            $this->components->twoColumnDetail("<fg=yellow>{$path}</>", 'file not found');

            return null;
        }

        if ($this->option('dry-run')) {
            $this->converted++;
            $this->components->twoColumnDetail($path, '<fg=green>would convert</>');

            return null;
        }

        $new = ImageStore::adopt($source['contents'], $folder, $name);

        if ($new === null) {
            $this->missing++;
            $this->components->twoColumnDetail("<fg=yellow>{$path}</>", 'could not be read');

            return null;
        }

        $this->converted++;
        $this->components->twoColumnDetail($path, "<fg=green>{$new}</>");

        if ($this->option('prune')) {
            $this->prune($source);
        }

        return $new;
    }

    /** @param array{contents: string, disk: ?string, file: ?string} $source */
    private function prune(array $source): void
    {
        if ($source['disk'] !== null) {
            Storage::disk('public')->delete($source['disk']);

            return;
        }

        if ($source['file'] !== null && $this->isPrunable($source['file'])) {
            @unlink($source['file']);
        }
    }

    /**
     * Only an uploaded file may be pruned. public/assets holds the shipped
     * demo images: they are in version control and the seeder points at them.
     */
    private function isPrunable(string $source): bool
    {
        return ! str_contains(str_replace('\\', '/', $source), '/public/assets/');
    }

    /**
     * Both eras of storage, plus the shipped assets the seed data points at.
     *
     * @return array{contents: string, disk: ?string, file: ?string}|null
     */
    private function locate(string $path): ?array
    {
        if (Storage::disk('public')->exists($path)) {
            return [
                'contents' => (string) Storage::disk('public')->get($path),
                'disk' => $path,
                'file' => null,
            ];
        }

        $candidates = [
            public_path('storage/'.$path),
            public_path('assets/img/products/'.$path),
            public_path('assets/img/'.$path),
            public_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return [
                    'contents' => (string) file_get_contents($candidate),
                    'disk' => null,
                    'file' => $candidate,
                ];
            }
        }

        return null;
    }
}
