<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\TimezoneService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SyncTimezoneRequest;
use Illuminate\Http\JsonResponse;

class SyncTimezoneController extends Controller
{
    public function __invoke(SyncTimezoneRequest $request, TimezoneService $timezoneService): JsonResponse
    {
        $user = $request->user();
        $timezone = $timezoneService->syncForUser($user, (string) $request->validated('timezone'));
        $timezoneService->apply($user);

        return response()->json([
            'timezone' => $timezone,
        ]);
    }
}
