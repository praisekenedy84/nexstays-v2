<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Bar\Models\BarTab;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BarController extends Controller
{
    public function index(): View
    {
        $outlet = Outlet::query()->where('type', 'bar')->where('is_active', true)->firstOrFail();

        $tabs = BarTab::query()
            ->with(['order.items.menuItem'])
            ->where('outlet_id', $outlet->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        return view('modules.bar.index', compact('outlet', 'tabs'));
    }
}
