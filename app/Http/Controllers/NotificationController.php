<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->middleware('auth');
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'is_read' => 'nullable|in:0,1',
            'user_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50,100',
        ]);

        $perPage = (int)($validated['per_page'] ?? 25);
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

        $query = \App\Models\Notification::query()
            ->with(['user'])
            ->when(($validated['q'] ?? null), fn($q,$term)=>$q->where('message','like',"%{$term}%"))
            ->when(($validated['type'] ?? null), fn($q,$t)=>$q->where('type',$t))
            ->when(($validated['is_read'] ?? null) !== null, fn($q)=>$q->where('is_read', request('is_read')==='1'))
            ->when(($validated['from_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','>=',$d))
            ->when(($validated['to_date'] ?? null), fn($q,$d)=>$q->whereDate('created_at','<=',$d))
            ->orderByDesc('created_at');

        // Scope: Admin can view all; others only own notifications
        if ($user && $user->hasRole('Admin')) {
            if (!empty($validated['user_id'])) {
                $query->where('user_id', (int)$validated['user_id']);
            }
        } else {
            $query->where('user_id', optional($user)->id ?? 0);
        }

        $notifications = $query->paginate($perPage)->appends($request->query());
        return view('notifications.index', compact('notifications'));
    }

    public function destroy(int $notificationId): RedirectResponse
    {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
        $n = \App\Models\Notification::findOrFail($notificationId);
        // Authorization: Admin can delete any; others can delete their own
        if (!($user && ($user->hasRole('Admin') || $n->user_id === $user->id))) {
            abort(403);
        }
        $n->delete();
        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Bulk delete notifications by IDs. Admin can delete any; users can delete their own.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $ids = array_unique(array_map('intval', $validated['ids'] ?? []));
        if (empty($ids)) {
            return back()->with('error', 'No notifications selected.');
        }
        $q = \App\Models\Notification::whereIn('id', $ids);
        if (!($user && $user->hasRole('Admin'))) {
            $q->where('user_id', optional($user)->id ?? 0);
        }
        $count = (clone $q)->count();
        if ($count === 0) {
            return back()->with('error', 'No notifications deleted.');
        }
        $q->delete();
        return back()->with('success', "Deleted {$count} notification(s).");
    }

    /**
     * Admin cleanup: delete read notifications, optionally older than N days.
     */
    public function destroyRead(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!($user && $user->hasRole('Admin'))) {
            abort(403);
        }
        $validated = $request->validate([
            'older_days' => 'nullable|integer|min:0|max:3650',
        ]);
        $olderDays = (int)($validated['older_days'] ?? 0);
        $q = \App\Models\Notification::query()->where('is_read', true);
        if ($olderDays > 0) {
            $q->where('created_at', '<', now()->subDays($olderDays));
        }
        $count = (clone $q)->count();
        $q->delete();
        return back()->with('success', $count > 0 ? "Deleted {$count} read notification(s)." : 'No read notifications matched criteria.');
    }

    /**
     * Admin cleanup: delete notifications older than N days (read or unread).
     */
    public function destroyOlder(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!($user && $user->hasRole('Admin'))) {
            abort(403);
        }
        $validated = $request->validate([
            'days' => 'required|integer|min:7|max:3650',
        ]);
        $days = (int)$validated['days'];
        $count = \App\Models\Notification::where('created_at', '<', now()->subDays($days))->count();
        \App\Models\Notification::where('created_at', '<', now()->subDays($days))->delete();
        return back()->with('success', $count > 0 ? "Purged {$count} notification(s) older than {$days} days." : 'No notifications matched purge criteria.');
    }

    public function markAllRead(): RedirectResponse
    {
        $userId = (int)Auth::id();
        $this->notifications->markAllAsRead($userId);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function markRead(int $notificationId): RedirectResponse
    {
        $userId = (int)Auth::id();
        // Ensure the notification belongs to the current user
        $n = \App\Models\Notification::where('id', $notificationId)->where('user_id', $userId)->firstOrFail();
        $this->notifications->markAsRead($n->id);
        // If URL is present, redirect there; else fallback back
        if (!empty($n->url)) {
            return redirect()->to($n->url);
        }
        return back();
    }
}
