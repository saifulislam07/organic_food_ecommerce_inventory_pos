<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingController extends Controller
{
    /**
     * The settings this panel owns. Anything not listed here is ignored, and the
     * stored type comes from this table rather than a hidden field in the form —
     * the browser should not get to decide how a value is interpreted.
     */
    private const SCHEMA = [
        'site_title' => ['type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
        'logo' => ['type' => 'image', 'rules' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
        'hero_title' => ['type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
        'hero_desc' => ['type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
        'whatsapp' => ['type' => 'text', 'rules' => ['nullable', 'string', 'max:32']],
        'phone' => ['type' => 'text', 'rules' => ['nullable', 'string', 'max:32']],
        'address' => ['type' => 'textarea', 'rules' => ['nullable', 'string', 'max:1000']],
        'facebook' => ['type' => 'text', 'rules' => ['nullable', 'url', 'max:255']],
        'youtube' => ['type' => 'text', 'rules' => ['nullable', 'url', 'max:255']],
        'instagram' => ['type' => 'text', 'rules' => ['nullable', 'url', 'max:255']],
        'tiktok' => ['type' => 'text', 'rules' => ['nullable', 'url', 'max:255']],
        'shipping_fee_inside' => ['type' => 'text', 'rules' => ['nullable', 'numeric', 'min:0']],
        'shipping_fee_outside' => ['type' => 'text', 'rules' => ['nullable', 'numeric', 'min:0']],
        'free_delivery_threshold' => ['type' => 'text', 'rules' => ['nullable', 'numeric', 'min:0']],
    ];

    public function index()
    {
        $allSettings = Setting::all();

        return view('admin.settings.index', compact('allSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate($this->rules(), [], $this->attributeNames());

        foreach (self::SCHEMA as $key => $definition) {
            if ($definition['type'] === 'image') {
                $this->storeImage($request, $key);

                continue;
            }

            // A field the submitted form did not carry must not wipe what is stored.
            if (! $request->has($key)) {
                continue;
            }

            Setting::updateOrCreate(['key' => $key], [
                'value_en' => $validated[$key]['value_en'] ?? null,
                'value_bn' => $validated[$key]['value_bn'] ?? null,
                'type' => $definition['type'],
            ]);
        }

        return back()->with('success', 'Settings updated successfully');
    }

    private function rules(): array
    {
        $rules = [];

        foreach (self::SCHEMA as $key => $definition) {
            $rules["{$key}.value_en"] = $definition['rules'];

            if ($definition['type'] !== 'image') {
                $rules["{$key}.value_bn"] = $definition['rules'];
            }
        }

        return $rules;
    }

    /** Turns "shipping_fee_inside.value_en" into "shipping fee inside (English)". */
    private function attributeNames(): array
    {
        $names = [];

        foreach (array_keys(self::SCHEMA) as $key) {
            $label = str_replace('_', ' ', $key);
            $names["{$key}.value_en"] = "{$label} (English)";
            $names["{$key}.value_bn"] = "{$label} (Bengali)";
        }

        return $names;
    }

    private function storeImage(Request $request, string $key): void
    {
        if (! $request->hasFile("{$key}.value_en")) {
            return;
        }

        $existing = Setting::where('key', $key)->first();
        $path = ImageStore::put($request->file("{$key}.value_en"), 'settings');

        // Replacing an image should not leave the old file orphaned on disk.
        if ($existing?->value_en && $existing->value_en !== $path) {
            ImageStore::delete($existing->value_en);
            Storage::disk('public')->delete($existing->value_en);
        }

        Setting::updateOrCreate(['key' => $key], [
            'value_en' => $path,
            'value_bn' => $path,
            'type' => 'image',
        ]);
    }
}
