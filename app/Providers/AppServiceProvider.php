<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Support\HbmsNavigation;
use Illuminate\Support\Facades\Blade;
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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Blade::directive('money', function (string $expression): string {
            return "<?php echo \\App\\Support\\MoneyFormatter::format($expression); ?>";
        });

        View::composer(['components.layout.sidebar', 'components.layout.header', 'components.layouts.app'], function ($view): void {
            $view->with('hbmsNavigation', HbmsNavigation::forUser(auth()->user()));
            $view->with('tenantLabel', tenant('id') ?? config('app.name'));
        });
    }
}
