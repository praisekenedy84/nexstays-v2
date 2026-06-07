<?php

declare(strict_types=1);

namespace App\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationBell extends Component
{
    public function render(): View|Closure|string
    {
        return view('components.layout.notification-bell');
    }
}
