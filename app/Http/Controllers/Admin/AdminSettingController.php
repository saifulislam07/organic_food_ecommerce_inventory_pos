<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class AdminSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value_en', 'key'); // For default values if needed
        $allSettings = Setting::all();
        return view('admin.settings.index', compact('allSettings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $values) {
            if ($request->hasFile("{$key}.value_en")) {
                $file = $request->file("{$key}.value_en");
                $path = $file->store('settings', 'public');
                Setting::updateOrCreate(['key' => $key], [
                    'value_en' => $path,
                    'value_bn' => $path,
                    'type' => 'image'
                ]);
                continue;
            }

            if (is_array($values)) {
                Setting::updateOrCreate(['key' => $key], [
                    'value_en' => $values['value_en'] ?? null,
                    'value_bn' => $values['value_bn'] ?? null,
                    'type' => $values['type'] ?? 'text'
                ]);
            }
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
