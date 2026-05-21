<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Shared\Models\Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'outlet_id' => $this->outlet_id,
            'table_id' => $this->table_id,
            'folio_id' => $this->folio_id,
            'status' => $this->status,
            'guest_label' => $this->guest_label,
            'covers' => $this->covers,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_logs' => OrderStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'table' => $this->whenLoaded('table', fn () => [
                'id' => $this->table?->id,
                'table_number' => $this->table?->table_number,
            ]),
        ];
    }
}
