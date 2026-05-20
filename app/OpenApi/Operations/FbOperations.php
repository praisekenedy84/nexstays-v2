<?php

declare(strict_types=1);

namespace App\OpenApi\Operations;

use OpenApi\Attributes as OA;

final class FbOperations
{
    #[OA\Get(path: '/outlets', operationId: 'outletsIndex', summary: 'List outlets', tags: ['Outlets'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Outlet list')])]
    public function outletsIndex(): void
    {
    }

    #[OA\Get(path: '/outlets/{outlet}', operationId: 'outletsShow', summary: 'Get outlet', tags: ['Outlets'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Outlet resource')])]
    public function outletsShow(): void
    {
    }

    #[OA\Get(path: '/outlets/{outlet}/tables', operationId: 'outletsTables', summary: 'List outlet tables', tags: ['Outlets'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Table map')])]
    public function outletsTables(): void
    {
    }

    #[OA\Get(path: '/outlets/{outlet}/menu', operationId: 'outletsMenu', summary: 'Outlet menu catalog', tags: ['Outlets'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Categories and items')])]
    public function outletsMenu(): void
    {
    }

    #[OA\Get(path: '/outlets/{outlet}/orders', operationId: 'outletOrdersIndex', summary: 'List open orders for outlet', tags: ['Orders'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Order list')])]
    public function outletOrdersIndex(): void
    {
    }

    #[OA\Post(
        path: '/outlets/{outlet}/orders',
        operationId: 'outletOrdersStore',
        summary: 'Create POS order',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'table_id', type: 'string', format: 'uuid', nullable: true),
            new OA\Property(property: 'folio_id', type: 'string', format: 'uuid', nullable: true),
            new OA\Property(property: 'covers', type: 'integer'),
            new OA\Property(
                property: 'items',
                type: 'array',
                items: new OA\Items(properties: [
                    new OA\Property(property: 'menu_item_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                ])
            ),
        ])),
        responses: [new OA\Response(response: 201, description: 'Order created')]
    )]
    public function outletOrdersStore(): void
    {
    }

    #[OA\Get(path: '/orders/{order}', operationId: 'ordersShow', summary: 'Get order', tags: ['Orders'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Order with line items')])]
    public function ordersShow(): void
    {
    }

    #[OA\Post(
        path: '/orders/{order}/items',
        operationId: 'ordersAddItems',
        summary: 'Add line items to order',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['items'],
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(required: ['menu_item_id', 'quantity'], properties: [
                    new OA\Property(property: 'menu_item_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                ])),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Updated order')]
    )]
    public function ordersAddItems(): void
    {
    }

    #[OA\Post(path: '/orders/{order}/fire', operationId: 'ordersFire', summary: 'Send order to kitchen (KDS)', tags: ['Orders'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Order fired')])]
    public function ordersFire(): void
    {
    }

    #[OA\Post(
        path: '/orders/{order}/post-to-folio',
        operationId: 'ordersPostToFolio',
        summary: 'Post order total to guest folio',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['folio_id'], properties: [
            new OA\Property(property: 'folio_id', type: 'string', format: 'uuid'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Charge posted to folio')]
    )]
    public function ordersPostToFolio(): void
    {
    }

    #[OA\Post(
        path: '/orders/{order}/cash-payment',
        operationId: 'ordersCashPayment',
        summary: 'Settle order with cash at POS',
        description: 'Requires an active till session for the outlet.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['till_session_id', 'amount_tendered'],
            properties: [
                new OA\Property(property: 'till_session_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount_tendered', type: 'number', format: 'float'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Payment recorded'), new OA\Response(response: 422, description: 'Till closed or validation error')]
    )]
    public function ordersCashPayment(): void
    {
    }

    #[OA\Get(path: '/outlets/{outlet}/tabs', operationId: 'barTabsIndex', summary: 'List open bar tabs', tags: ['Bar tabs'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Open tabs')])]
    public function barTabsIndex(): void
    {
    }

    #[OA\Post(
        path: '/outlets/{outlet}/tabs',
        operationId: 'barTabsStore',
        summary: 'Open bar tab',
        tags: ['Bar tabs'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'folio_id', type: 'string', format: 'uuid', nullable: true),
            new OA\Property(property: 'guest_label', type: 'string'),
        ])),
        responses: [new OA\Response(response: 201, description: 'Tab opened')]
    )]
    public function barTabsStore(): void
    {
    }
}
