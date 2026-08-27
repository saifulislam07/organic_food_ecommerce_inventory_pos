<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SmtpTestMail;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminMailSettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.mail', [
            'mail' => MailSettings::all(),
            'isConfigured' => MailSettings::isConfigured(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            // Left blank on purpose keeps whatever is already stored.
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        MailSettings::save($validated);

        return redirect()->route('admin.settings.mail.edit')->with('success', 'Mail settings saved.');
    }

    public function test(Request $request)
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        if (! MailSettings::isConfigured()) {
            return back()->withErrors(['test_email' => 'Save your SMTP settings before sending a test.']);
        }

        MailSettings::apply();

        try {
            Mail::to($validated['test_email'])->send(new SmtpTestMail);
        } catch (\Throwable $e) {
            return back()->withErrors(['test_email' => 'Could not send: '.$e->getMessage()]);
        }

        return back()->with('success', "Test email sent to {$validated['test_email']}.");
    }
}
