<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = Notification::forRecipient($request->user())
            ->latest('created_at')
            ->limit(100)
            ->get();

        // Not `notifications` — that key is the shared bell prop from HandleInertiaRequests.
        return Inertia::render('notifications/index', [
            'notification_list' => $notifications,
        ]);
    }

    public function markRead(Request $request): RedirectResponse
    {
        $query = Notification::forRecipient($request->user());

        if ($request->filled('ids')) {
            $query->whereIn('Notification_id', (array) $request->input('ids'));
        } elseif ($request->filled('id')) {
            $query->where('Notification_id', $request->input('id'));
        }

        $query->update(['is_read' => true]);

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        Notification::forRecipient($request->user())->delete();

        return back()->with('success', 'Notifications cleared.');
    }
}
