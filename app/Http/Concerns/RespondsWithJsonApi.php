<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

trait RespondsWithJsonApi
{
    protected function respond(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $payload = $data->response()->getData(true);

            return response()->json([
                'data' => $payload['data'] ?? $payload,
                'meta' => array_merge($this->baseMeta(), $payload['meta'] ?? [], $meta),
            ], $status);
        }

        return response()->json([
            'data' => $data,
            'meta' => array_merge($this->baseMeta(), $meta),
        ], $status);
    }

    protected function respondCollection(ResourceCollection $collection, int $status = 200): JsonResponse
    {
        $payload = $collection->response()->getData(true);

        return response()->json([
            'data' => $payload['data'] ?? [],
            'meta' => array_merge($this->baseMeta(), $payload['meta'] ?? []),
            'links' => $payload['links'] ?? null,
        ], $status);
    }

    protected function respondError(
        string $code,
        string $message,
        int $status = 422,
        array $details = []
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details ?: null,
                'request_id' => (string) Str::uuid(),
            ],
        ], $status);
    }

    /**
     * @return array<string, string>
     */
    protected function baseMeta(): array
    {
        return [
            'request_id' => (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
