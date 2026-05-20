<?php

declare(strict_types=1);

namespace App\OpenApi\Operations;

use OpenApi\Attributes as OA;

final class AuthOperations
{
    #[OA\Post(
        path: '/auth/login',
        operationId: 'authLogin',
        summary: 'Obtain API bearer token',
        description: 'Authenticates staff credentials and returns a Sanctum personal access token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthLoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthLoginData'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/JsonApiMeta'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid credentials or validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function login(): void
    {
    }

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'authLogout',
        summary: 'Revoke current token',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(): void
    {
    }

    #[OA\Get(
        path: '/auth/me',
        operationId: 'authMe',
        summary: 'Current authenticated user',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User profile with roles and permissions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(): void
    {
    }
}
