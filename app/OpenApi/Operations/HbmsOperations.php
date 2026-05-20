<?php

declare(strict_types=1);

namespace App\OpenApi\Operations;

use OpenApi\Attributes as OA;

final class HbmsOperations
{
    #[OA\Get(
        path: '/availability',
        operationId: 'availabilityIndex',
        summary: 'Search room availability',
        description: 'Public endpoint. Returns available room types and rates for the stay window.',
        tags: ['Availability'],
        parameters: [
            new OA\Parameter(name: 'check_in', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\Parameter(name: 'check_out', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-05')),
            new OA\Parameter(name: 'adults', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 10, default: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Availability matrix'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function availabilityIndex(): void
    {
    }

    #[OA\Get(path: '/guests', operationId: 'guestsIndex', summary: 'List guests', tags: ['Guests'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Paginated guest list'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 403, description: 'Forbidden')])]
    public function guestsIndex(): void
    {
    }

    #[OA\Get(path: '/guests/{guest}', operationId: 'guestsShow', summary: 'Get guest', tags: ['Guests'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Guest resource'), new OA\Response(response: 404, description: 'Not found')])]
    public function guestsShow(): void
    {
    }

    #[OA\Post(
        path: '/guests',
        operationId: 'guestsStore',
        summary: 'Create guest',
        tags: ['Guests'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['first_name', 'last_name'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Guest created'), new OA\Response(response: 422, description: 'Validation error')]
    )]
    public function guestsStore(): void
    {
    }

    #[OA\Patch(path: '/guests/{guest}', operationId: 'guestsUpdate', summary: 'Update guest', tags: ['Guests'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(type: 'object')), responses: [new OA\Response(response: 200, description: 'Updated guest')])]
    public function guestsUpdate(): void
    {
    }

    #[OA\Get(path: '/rooms', operationId: 'roomsIndex', summary: 'List rooms', tags: ['Rooms'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Room list with status')])]
    public function roomsIndex(): void
    {
    }

    #[OA\Get(path: '/rooms/{room}', operationId: 'roomsShow', summary: 'Get room', tags: ['Rooms'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Room resource')])]
    public function roomsShow(): void
    {
    }

    #[OA\Patch(
        path: '/rooms/{room}/status',
        operationId: 'roomsUpdateStatus',
        summary: 'Update room housekeeping status',
        tags: ['Rooms'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['status'],
            properties: [new OA\Property(property: 'status', type: 'string', enum: ['vacant', 'occupied', 'dirty', 'clean', 'inspected', 'out_of_order'])]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated room')]
    )]
    public function roomsUpdateStatus(): void
    {
    }

    #[OA\Get(path: '/room-types', operationId: 'roomTypesIndex', summary: 'List room types', tags: ['Room types'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Room type catalog')])]
    public function roomTypesIndex(): void
    {
    }

    #[OA\Get(path: '/room-types/{roomType}', operationId: 'roomTypesShow', summary: 'Get room type', tags: ['Room types'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'roomType', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Room type resource')])]
    public function roomTypesShow(): void
    {
    }

    #[OA\Get(path: '/reservations', operationId: 'reservationsIndex', summary: 'List reservations', tags: ['Reservations'], security: [['sanctum' => []]], parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'check_in_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'check_in_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
    ], responses: [new OA\Response(response: 200, description: 'Reservation list')])]
    public function reservationsIndex(): void
    {
    }

    #[OA\Get(path: '/reservations/{reservation}', operationId: 'reservationsShow', summary: 'Get reservation', tags: ['Reservations'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Reservation with relationships')])]
    public function reservationsShow(): void
    {
    }

    #[OA\Post(
        path: '/reservations',
        operationId: 'reservationsStore',
        summary: 'Create reservation',
        tags: ['Reservations'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['guest_id', 'room_type_id', 'check_in_date', 'check_out_date', 'adults'],
            properties: [
                new OA\Property(property: 'guest_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'room_type_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'room_id', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'check_in_date', type: 'string', format: 'date'),
                new OA\Property(property: 'check_out_date', type: 'string', format: 'date'),
                new OA\Property(property: 'adults', type: 'integer', minimum: 1, maximum: 10),
                new OA\Property(property: 'children', type: 'integer', minimum: 0, maximum: 10),
                new OA\Property(property: 'status', type: 'string', enum: ['inquiry', 'confirmed']),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Reservation created'), new OA\Response(response: 422, description: 'Domain or validation error')]
    )]
    public function reservationsStore(): void
    {
    }

    #[OA\Patch(path: '/reservations/{reservation}', operationId: 'reservationsUpdate', summary: 'Update reservation', tags: ['Reservations'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(type: 'object')), responses: [new OA\Response(response: 200, description: 'Updated reservation')])]
    public function reservationsUpdate(): void
    {
    }

    #[OA\Delete(path: '/reservations/{reservation}', operationId: 'reservationsDestroy', summary: 'Cancel reservation', tags: ['Reservations'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Cancelled'), new OA\Response(response: 422, description: 'Invalid state transition')])]
    public function reservationsDestroy(): void
    {
    }

    #[OA\Post(
        path: '/reservations/{reservation}/check-in',
        operationId: 'reservationsCheckIn',
        summary: 'Check in guest',
        description: 'Transitions reservation to checked_in, assigns room, opens folio.',
        tags: ['Reservations'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'room_id', type: 'string', format: 'uuid'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Folio opened'), new OA\Response(response: 422, description: 'Domain rule violation', content: new OA\JsonContent(ref: '#/components/schemas/JsonApiError'))]
    )]
    public function reservationsCheckIn(): void
    {
    }

    #[OA\Post(
        path: '/reservations/{reservation}/check-out',
        operationId: 'reservationsCheckOut',
        summary: 'Check out guest',
        tags: ['Reservations'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'settle_folio', type: 'boolean', default: false),
        ])),
        responses: [new OA\Response(response: 200, description: 'Checked out')]
    )]
    public function reservationsCheckOut(): void
    {
    }

    #[OA\Get(path: '/folios/{folio}', operationId: 'foliosShow', summary: 'Get folio with transactions', tags: ['Folios'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'folio', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Folio and ledger')])]
    public function foliosShow(): void
    {
    }

    #[OA\Post(
        path: '/folios/{folio}/transactions',
        operationId: 'foliosPostCharge',
        summary: 'Post charge to folio',
        tags: ['Folios'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'folio', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['type', 'description', 'amount'],
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'ancillary'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'amount', type: 'number', format: 'float'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Transaction posted')]
    )]
    public function foliosPostCharge(): void
    {
    }
}
