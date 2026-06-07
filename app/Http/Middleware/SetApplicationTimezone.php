<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Services\TimezoneService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationTimezone
{
    public function __construct(
        private readonly TimezoneService $timezoneService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->timezoneService->apply($request->user());

        return $next($request);
    }
}
