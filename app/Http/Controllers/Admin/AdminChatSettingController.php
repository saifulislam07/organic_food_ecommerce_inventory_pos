<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Sms\SmsManager;
use App\Support\ChatSettings;
use Illuminate\Http\Request;

/**
 * The WhatsApp and Messenger buttons that float over the storefront.
 */
class AdminChatSettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.chat', [
            'chat' => ChatSettings::all(),
            'whatsappNumber' => ChatSettings::whatsappNumber(),
            'whatsappUrl' => ChatSettings::whatsappUrl(),
            'messengerUrl' => ChatSettings::messengerUrl(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'chat_whatsapp_number' => ['nullable', 'string', 'max:20'],
            'chat_whatsapp_message_en' => ['nullable', 'string', 'max:255'],
            'chat_whatsapp_message_bn' => ['nullable', 'string', 'max:255'],
            'chat_messenger_id' => ['nullable', 'string', 'max:255'],
            'chat_position' => ['nullable', 'in:left,right'],
        ]);

        // A number that wa.me cannot dial would give a button that goes nowhere.
        if (filled($validated['chat_whatsapp_number'] ?? null)
            && SmsManager::normalise($validated['chat_whatsapp_number']) === null) {
            return back()
                ->withInput()
                ->withErrors(['chat_whatsapp_number' => 'That does not look like a mobile number.']);
        }

        $validated['chat_whatsapp_enabled'] = $request->boolean('chat_whatsapp_enabled') ? '1' : '';
        $validated['chat_messenger_enabled'] = $request->boolean('chat_messenger_enabled') ? '1' : '';
        $validated['chat_position'] = $validated['chat_position'] ?? 'right';

        ChatSettings::save($validated);

        return redirect()->route('admin.settings.chat.edit')->with('success', 'Chat settings saved.');
    }
}
