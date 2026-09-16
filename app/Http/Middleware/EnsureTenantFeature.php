<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantFeature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $required = $features === []
            ? []
            : array_values(array_filter(array_map('trim', explode(',', implode(',', $features)))));

        if ($required !== [] && ! TenantFeatures::allowsAny($required)) {
            abort(403, 'This module is not enabled for this property.');
        }

        return $next($request);
    }
}
