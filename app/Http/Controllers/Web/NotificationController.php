<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\PropertyNotification;
use App\Domain\Shared\Services\NotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function preview(): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notifications->unreadCount(),
            'recent' => $this->notifications->recent(8)->map(fn (PropertyNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'is_unread' => $notification->isUnread(),
                'created_at' => $notification->created_at->diffForHumans(),
                'read_url' => route('tenant.notifications.read', $notification),
            ])->values()->all(),
        ]);
    }

    public function index(Request $request): View
    {
        $type = $request->query('type');

        $query = PropertyNotification::query()->latest();

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate(25)->withQueryString();

        $types = PropertyNotification::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('modules.notifications.index', compact('notifications', 'types', 'type'));
    }

    public function markRead(PropertyNotification $notification): RedirectResponse
    {
        $this->notifications->markRead($notification);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $this->notifications->markAllRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
