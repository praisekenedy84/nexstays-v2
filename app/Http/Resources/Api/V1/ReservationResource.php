<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\HBMS\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reservation */
class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'reservation',
            'attributes' => [
                'booking_ref' => $this->booking_ref,
                'status' => $this->status,
                'check_in_date' => $this->check_in_date?->toDateString(),
                'check_out_date' => $this->check_out_date?->toDateString(),
                'adults' => $this->adults,
                'children' => $this->children,
                'daily_rate' => $this->daily_rate,
                'deposit_amount' => $this->deposit_amount,
                'source' => $this->source,
                'ota_ref' => $this->ota_ref,
                'special_requests' => $this->special_requests,
                'guest_id' => $this->guest_id,
                'room_id' => $this->room_id,
                'room_type_id' => $this->room_type_id,
                'rate_plan_id' => $this->rate_plan_id,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'guest' => $this->whenLoaded('guest', fn () => [
                    'id' => $this->guest->id,
                    'name' => trim("{$this->guest->first_name} {$this->guest->last_name}"),
                ]),
                'room' => $this->whenLoaded('room', fn () => $this->room ? [
                    'id' => $this->room->id,
                    'room_number' => $this->room->room_number,
                    'status' => $this->room->status,
                ] : null),
                'room_type' => $this->whenLoaded('roomType', fn () => [
                    'id' => $this->roomType->id,
                    'name' => $this->roomType->name,
                    'code' => $this->roomType->code,
                ]),
                'folio' => $this->whenLoaded('folio', fn () => $this->folio
                    ? FolioResource::make($this->folio)
                    : null),
            ],
        ];
    }
}
