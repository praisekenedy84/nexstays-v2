<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — central + versioned REST (NexStay TRD §5)
|--------------------------------------------------------------------------
| Tenant-scoped endpoints run under tenant subdomains via routes/tenant.php
| or dedicated API domain configuration.
*/
Route::prefix('v1')->name('central.')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'data' => [
                'app' => config('app.name'),
                'status' => 'ok',
                'time' => now()->toIso8601String(),
            ],
            'meta' => [
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
            ],
        ]);
    })->name('health');
});
