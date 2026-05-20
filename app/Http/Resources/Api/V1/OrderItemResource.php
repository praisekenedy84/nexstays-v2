<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Shared\Models\OrderItem */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'name' => $this->whenLoaded('menuItem', fn () => $this->menuItem?->name),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
