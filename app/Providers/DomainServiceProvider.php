<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\ReportingService;
use App\Domain\Shared\Services\TaxService;
use App\Domain\Till\Services\TillSessionService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaxService::class);
        $this->app->singleton(FolioService::class);
        $this->app->singleton(TillSessionService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(ReportingService::class);
    }
}
