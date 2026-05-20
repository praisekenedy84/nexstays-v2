<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'JsonApiMeta',
    properties: [
        new OA\Property(property: 'request_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'JsonApiEnvelope',
    properties: [
        new OA\Property(property: 'data'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/JsonApiMeta'),
    ]
)]
#[OA\Schema(
    schema: 'JsonApiError',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message', 'request_id'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'DOMAIN_RULE_VIOLATION'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'details', nullable: true),
                new OA\Property(property: 'request_id', type: 'string', format: 'uuid'),
            ],
            type: 'object'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'HealthData',
    properties: [
        new OA\Property(property: 'app', type: 'string', example: 'NexStay'),
        new OA\Property(property: 'tenant', type: 'string', example: 'demo', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'ok'),
        new OA\Property(property: 'time', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AuthLoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@demo.local'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NexStay2026!'),
        new OA\Property(property: 'device_name', type: 'string', maxLength: 100, example: 'swagger-ui'),
    ]
)]
#[OA\Schema(
    schema: 'AuthLoginData',
    properties: [
        new OA\Property(
            property: 'user',
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'type', type: 'string', example: 'user'),
                new OA\Property(
                    property: 'attributes',
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                    ],
                    type: 'object'
                ),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'token', type: 'string', description: 'Plain-text Sanctum token (shown once)'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UuidPath',
    type: 'string',
    format: 'uuid'
)]
final class Schemas
{
}
