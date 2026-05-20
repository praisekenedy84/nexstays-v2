<?php

declare(strict_types=1);

namespace App\OpenApi\Operations;

use OpenApi\Attributes as OA;

final class SystemOperations
{
    #[OA\Get(
        path: '/health',
        operationId: 'tenantHealth',
        summary: 'Tenant health check',
        description: 'No authentication required. Confirms tenancy context and API reachability.',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service healthy',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/HealthData'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/JsonApiMeta'),
                    ]
                )
            ),
        ]
    )]
    public function health(): void
    {
    }
}
