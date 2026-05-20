<?php

declare(strict_types=1);

namespace App\OpenApi\Operations;

use OpenApi\Attributes as OA;

final class SupportOperations
{
    #[OA\Post(
        path: '/tills/open',
        operationId: 'tillsOpen',
        summary: 'Open till session',
        tags: ['Till'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['outlet_id', 'opening_float'],
            properties: [
                new OA\Property(property: 'outlet_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'opening_float', type: 'number', format: 'float'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Till session opened')]
    )]
    public function tillsOpen(): void
    {
    }

    #[OA\Get(path: '/tills/active', operationId: 'tillsActive', summary: 'Active till sessions', tags: ['Till'], security: [['sanctum' => []]], parameters: [
        new OA\Parameter(name: 'outlet_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
    ], responses: [new OA\Response(response: 200, description: 'Active sessions')])]
    public function tillsActive(): void
    {
    }

    #[OA\Post(
        path: '/tills/{till}/close',
        operationId: 'tillsClose',
        summary: 'Close till session',
        tags: ['Till'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'till', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['counted_cash'],
            properties: [
                new OA\Property(property: 'counted_cash', type: 'number', format: 'float'),
                new OA\Property(property: 'notes', type: 'string'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Till closed with variance report')]
    )]
    public function tillsClose(): void
    {
    }

    #[OA\Get(path: '/tills/history', operationId: 'tillsHistory', summary: 'Till session history', tags: ['Till'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Historical sessions')])]
    public function tillsHistory(): void
    {
    }

    #[OA\Get(path: '/inventory/stock-items', operationId: 'stockItemsIndex', summary: 'List stock items', tags: ['Inventory'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Stock catalog')])]
    public function stockItemsIndex(): void
    {
    }

    #[OA\Post(path: '/inventory/stock-items', operationId: 'stockItemsStore', summary: 'Create stock item', tags: ['Inventory'], security: [['sanctum' => []]], requestBody: new OA\RequestBody(content: new OA\JsonContent(type: 'object')), responses: [new OA\Response(response: 201, description: 'Created')])]
    public function stockItemsStore(): void
    {
    }

    #[OA\Patch(path: '/inventory/stock-items/{stockItem}', operationId: 'stockItemsUpdate', summary: 'Update stock item', tags: ['Inventory'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'stockItem', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function stockItemsUpdate(): void
    {
    }

    #[OA\Delete(path: '/inventory/stock-items/{stockItem}', operationId: 'stockItemsDestroy', summary: 'Delete stock item', tags: ['Inventory'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'stockItem', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 204, description: 'Deleted')])]
    public function stockItemsDestroy(): void
    {
    }

    #[OA\Post(path: '/outlets/{outlet}/menu/items', operationId: 'menuItemsStore', summary: 'Add menu item to outlet', tags: ['Menu'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'outlet', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 201, description: 'Menu item created')])]
    public function menuItemsStore(): void
    {
    }

    #[OA\Patch(path: '/menu-items/{menuItem}', operationId: 'menuItemsUpdate', summary: 'Update menu item', tags: ['Menu'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'menuItem', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function menuItemsUpdate(): void
    {
    }

    #[OA\Delete(path: '/menu-items/{menuItem}', operationId: 'menuItemsDestroy', summary: 'Delete menu item', tags: ['Menu'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'menuItem', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 204, description: 'Deleted')])]
    public function menuItemsDestroy(): void
    {
    }

    #[OA\Post(path: '/menu-items/{menuItem}/toggle', operationId: 'menuItemsToggle', summary: 'Toggle menu item availability', tags: ['Menu'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'menuItem', in: 'path', required: true, schema: new OA\Schema(ref: '#/components/schemas/UuidPath'))], responses: [new OA\Response(response: 200, description: 'Toggled')])]
    public function menuItemsToggle(): void
    {
    }

    #[OA\Get(path: '/reports/debts', operationId: 'reportsDebts', summary: 'Outstanding folio debts', tags: ['Reports'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Debt report')])]
    public function reportsDebts(): void
    {
    }

    #[OA\Get(path: '/reports/fb-revenue', operationId: 'reportsFbRevenue', summary: 'F&B revenue split report', tags: ['Reports'], security: [['sanctum' => []]], parameters: [
        new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
    ], responses: [new OA\Response(response: 200, description: 'Revenue breakdown')])]
    public function reportsFbRevenue(): void
    {
    }

    #[OA\Get(path: '/users', operationId: 'usersIndex', summary: 'List staff users', description: 'Requires manage-users permission.', tags: ['Users'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Staff list')])]
    public function usersIndex(): void
    {
    }
}
