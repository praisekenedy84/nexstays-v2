<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\HBMS\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoomType */
class RoomTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'room_type',
            'attributes' => [
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'max_adults' => $this->max_adults,
                'max_children' => $this->max_children,
                'base_rate' => $this->base_rate,
                'amenities' => $this->amenities,
                'rooms_count' => $this->whenCounted('rooms'),
            ],
        ];
    }
}
