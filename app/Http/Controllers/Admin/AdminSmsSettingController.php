<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Sms\SmsManager;
use App\Support\SmsSettings;
use Illuminate\Http\Request;

class AdminSmsSettingController extends Controller
{
    public function __construct(private readonly SmsManager $sms) {}

    public function edit()
    {
        return view('admin.settings.sms', [
            'sms' => SmsSettings::all(),
            'isConfigured' => SmsSettings::isConfigured(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'sms_driver' => ['required', 'in:log,bulksmsbd'],
            'sms_sender_id' => ['nullable', 'string', 'max:32'],
            // Blank keeps the stored key.
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_endpoint' => ['nullable', 'url', 'max:255'],
        ]);

        if ($validated['sms_driver'] !== 'log') {
            $request->validate([
                'sms_sender_id' => ['required', 'string', 'max:32'],
            ], [], ['sms_sender_id' => 'sender ID']);
        }

        SmsSettings::save($validated);

        return redirect()->route('admin.settings.sms.edit')->with('success', 'SMS settings saved.');
    }

    public function test(Request $request)
    {
        $validated = $request->validate([
            'test_number' => ['required', 'string', 'max:20'],
        ]);

        $number = SmsManager::normalise($validated['test_number']);

        if ($number === null) {
            return back()->withErrors(['test_number' => 'That does not look like a mobile number.']);
        }

        $result = $this->sms->send($number, 'MohiPure test message. If you received this, SMS is working.');

        if (! $result->sent) {
            return back()->withErrors(['test_number' => $result->error]);
        }

        $where = SmsSettings::isConfigured() ? "sent to {$number}" : "written to the log (driver is 'log')";

        return back()->with('success', "Test message {$where}.");
    }
}
