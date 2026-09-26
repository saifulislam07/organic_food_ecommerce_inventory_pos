<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        // A hidden box no human sees, so anything in it came from a script.
        if (filled($request->input('website'))) {
            return back()->withInput()->with('success', 'Thanks for reaching out!');
        }

        $request->merge([
            'phone' => preg_replace('/\D+/', '', (string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^(?:88)?01[3-9]\d{8}$/'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Please enter a valid Bangladeshi mobile number.',
        ]);

        ContactMessage::create($data);

        return back()->with('success', "Thanks for reaching out! We'll get back to you soon.");
    }
}
