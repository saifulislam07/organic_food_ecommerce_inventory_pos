<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SeoSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminSeoSettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.seo', [
            'seo' => SeoSettings::all(),
            'ogImageUrl' => SeoSettings::ogImageUrl(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'seo_meta_title' => ['nullable', 'string', 'max:70'],
            'seo_meta_description' => ['nullable', 'string', 'max:180'],
            'seo_meta_keywords' => ['nullable', 'string', 'max:255'],
            'seo_google_analytics' => ['nullable', 'string', 'max:32', 'regex:/^(G-[A-Z0-9]+|UA-\d+-\d+)$/i'],
            'seo_google_site_verification' => ['nullable', 'string', 'max:255'],
            // The values contain commas, so Rule::in is the only safe way to express this.
            'seo_robots' => ['nullable', Rule::in(['index, follow', 'noindex, nofollow'])],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'seo_google_analytics.regex' => 'Use a GA4 measurement ID like G-XXXXXXXXXX.',
            'seo_meta_title.max' => 'Search results cut the title around 70 characters.',
            'seo_meta_description.max' => 'Search results cut the description around 180 characters.',
        ]);

        $values = collect($validated)->except('og_image')->all();

        if ($request->hasFile('og_image')) {
            $previous = SeoSettings::get('seo_og_image');
            $values['seo_og_image'] = $request->file('og_image')->store('seo', 'public');

            // Replacing the share image should not leave the old file behind.
            if ($previous && $previous !== $values['seo_og_image']) {
                Storage::disk('public')->delete($previous);
            }
        }

        SeoSettings::save($values);

        return redirect()->route('admin.settings.seo.edit')->with('success', 'SEO settings saved.');
    }
}
