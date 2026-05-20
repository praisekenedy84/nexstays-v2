<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDocsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(config('nexstay.api_docs.enabled'), FILTER_VALIDATE_BOOL)) {
            abort(404);
        }

        return $next($request);
    }
}
