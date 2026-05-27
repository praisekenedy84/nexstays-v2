<?php

declare(strict_types=1);

namespace App\View\Components\Layout;

use App\Domain\Shared\Services\NotificationService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationBell extends Component
{
    public int $unreadCount;

    /** @var \Illuminate\Support\Collection<int, \App\Domain\Shared\Models\PropertyNotification> */
    public $recent;

    public function __construct(NotificationService $notifications)
    {
        $this->unreadCount = $notifications->unreadCount();
        $this->recent      = $notifications->recent(8);
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.notification-bell');
    }
}
