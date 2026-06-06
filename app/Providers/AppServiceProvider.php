<?php

namespace App\Providers;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Services\OrderAuthorizationService;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Support\HbmsNavigation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.nexstay');

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Blade::directive('money', function (string $expression): string {
            return "<?php echo \\App\\Support\\MoneyFormatter::format($expression); ?>";
        });

        View::composer(['components.layout.sidebar', 'components.layout.header', 'components.layouts.app'], function ($view): void {
            $view->with('hbmsNavigation', HbmsNavigation::forUser(auth()->user()));
            $view->with('tenantLabel', tenant('id') ?? config('app.name'));
        });

        $orderAuth = fn (): OrderAuthorizationService => app(OrderAuthorizationService::class);

        Gate::define('view-order', fn (?User $user, Order $order) => $user !== null && $orderAuth()->canView($user, $order));
        Gate::define('manage-order', fn (?User $user, Order $order) => $user !== null && $orderAuth()->canManage($user, $order));
        Gate::define('create-order', fn (?User $user) => $user !== null && $orderAuth()->canCreate($user));
    }
}
