<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Inventory\Listeners\DeductInventoryOnOrderClose;
use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Domain\Inventory\Services\InventoryAvailabilityService;
use App\Domain\Inventory\Services\InventoryDeductionService;
use App\Domain\Restaurant\Events\OrderClosed;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\ReportingService;
use App\Domain\Shared\Services\TaxService;
use App\Domain\Till\Services\TillSessionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaxService::class);
        $this->app->singleton(FolioService::class);
        $this->app->singleton(TillSessionService::class);
        $this->app->singleton(BeverageStockLinkService::class);
        $this->app->singleton(InventoryAvailabilityService::class);
        $this->app->singleton(InventoryDeductionService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(ReportingService::class);
    }

    public function boot(): void
    {
        Event::listen(OrderClosed::class, DeductInventoryOnOrderClose::class);
    }
}
