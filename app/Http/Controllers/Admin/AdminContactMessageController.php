<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class AdminContactMessageController extends Controller
{
    use BulkDeletes, SearchesRecords;
    use SortsRecords;

    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $messages = $this->applySearch(
            ContactMessage::query(),
            $request->input('search'),
            ['name', 'phone', 'message']
        )
            ->when($status === 'unread', fn ($q) => $q->unread())
            ->when($status === 'read', fn ($q) => $q->where('is_read', true))
        ;

        $this->applySort($messages, $request, [
            'name' => 'name',
            'created_at' => 'created_at',
        ], 'created_at');

        $messages = $messages->paginate(20)->withQueryString();

        $unreadCount = ContactMessage::unread()->count();

        return view('admin.contact-messages.index', compact('messages', 'status', 'unreadCount'));
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact-messages.index')->with('success', 'Message deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, ContactMessage::class);

        return $this->bulkResponse($result, 'messages', 'admin.contact-messages.index');
    }

    /** Toggling from the list, without needing a detail page. */
    public function read(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => ! $contactMessage->is_read]);

        return back();
    }
}
