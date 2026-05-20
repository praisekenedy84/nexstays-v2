<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\HBMS\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Room */
class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'room',
            'attributes' => [
                'room_number' => $this->room_number,
                'floor' => $this->floor,
                'status' => $this->status,
                'is_smoking' => $this->is_smoking,
                'room_type_id' => $this->room_type_id,
            ],
            'relationships' => [
                'room_type' => $this->whenLoaded('roomType', fn () => [
                    'id' => $this->roomType->id,
                    'name' => $this->roomType->name,
                    'code' => $this->roomType->code,
                ]),
            ],
        ];
    }
}
