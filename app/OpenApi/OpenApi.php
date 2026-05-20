<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'NexStay Tenant API',
    description: <<<'DESC'
Multi-tenant hotel operations REST API (HBMS, F&B, till, inventory).

**Base URL:** `http://{tenant}.localhost:8000/api/v1` (replace `{tenant}` with your property slug, e.g. `demo`).

**Authentication:** Call `POST /auth/login` to obtain a Sanctum bearer token, then send `Authorization: Bearer {token}` on protected routes.

**Response envelope:** Successful responses use `{ "data": ..., "meta": { "request_id", "timestamp" } }`. Errors use `{ "error": { "code", "message", "details", "request_id" } }`.

**Permissions:** Endpoints require Spatie permissions on the authenticated user (see route middleware in `routes/tenant.php`).

**Integrations:** Use the demo tenant (`demo.localhost`) for exploration; production tenants use the same paths on their subdomain.
DESC,
    contact: new OA\Contact(name: 'NexStay Platform', email: 'support@nexstay.local')
)]
#[OA\Server(
    url: 'http://{tenant}.localhost:8000/api/v1',
    description: 'Tenant API (subdomain identifies the property database)',
    variables: [
        new OA\ServerVariable(
            serverVariable: 'tenant',
            default: 'demo',
            description: 'Tenant subdomain slug (e.g. demo from nexstay:install-demo)'
        ),
    ]
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Central landlord API (health check only on central domains)'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Laravel Sanctum personal access token from POST /auth/login',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
#[OA\Tag(name: 'System', description: 'Health and metadata')]
#[OA\Tag(name: 'Auth', description: 'Sanctum token authentication')]
#[OA\Tag(name: 'Availability', description: 'Room availability search (public)')]
#[OA\Tag(name: 'Guests', description: 'Guest profiles')]
#[OA\Tag(name: 'Rooms', description: 'Room inventory and housekeeping status')]
#[OA\Tag(name: 'Room types', description: 'Room type catalog')]
#[OA\Tag(name: 'Reservations', description: 'Booking lifecycle')]
#[OA\Tag(name: 'Folios', description: 'Guest folio — single source of financial truth')]
#[OA\Tag(name: 'Outlets', description: 'Restaurant, bar, lounge outlets')]
#[OA\Tag(name: 'Orders', description: 'POS orders')]
#[OA\Tag(name: 'Bar tabs', description: 'Open bar tabs')]
#[OA\Tag(name: 'Till', description: 'Cash drawer sessions')]
#[OA\Tag(name: 'Inventory', description: 'Stock items')]
#[OA\Tag(name: 'Menu', description: 'Menu item management')]
#[OA\Tag(name: 'Reports', description: 'Operational reports')]
#[OA\Tag(name: 'Users', description: 'Staff directory')]
final class OpenApi
{
}
