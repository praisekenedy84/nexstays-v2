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
                'total_nights' => $this->total_nights,
                'total_amount' => $this->total_amount,
                'deposit_amount' => $this->deposit_amount,
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'cancellation_policy' => $this->cancellation_policy,
                'cancellation_nights_used' => $this->cancellation_nights_used,
                'cancellation_charge_amount' => $this->cancellation_charge_amount,
                'cancellation_refund_amount' => $this->cancellation_refund_amount,
                'cancellation_refund_percentage' => $this->cancellation_refund_percentage,
                'source' => $this->source,
                'ota_ref' => $this->ota_ref,
                'special_requests' => $this->special_requests,
                'guest_id' => $this->guest_id,
                'room_id' => $this->room_id,
                'room_type_id' => $this->room_type_id,
                'rate_plan_id' => $this->rate_plan_id,
                'created_by' => $this->created_by,
                'overstay' => $this->overstayIncrease() !== null ? array_merge($this->overstayIncrease(), [
                    'waiver_reason' => $this->overstay_waiver_reason,
                    'settled_at'    => $this->overstay_settled_at?->toIso8601String(),
                    'settled_by'    => $this->whenLoaded('overstaySettledBy', fn () => $this->overstaySettledBy?->name),
                    'payment'       => $this->whenLoaded('overstaySettlementPayment', fn () => $this->overstaySettlementPayment ? [
                        'amount' => $this->overstaySettlementPayment->amount,
                        'method' => $this->overstaySettlementPayment->method,
                        'notes'  => $this->overstaySettlementPayment->notes,
                    ] : null),
                ]) : null,
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
                'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ] : null),
                'folio' => $this->whenLoaded('folio', fn () => $this->folio
                    ? FolioResource::make($this->folio)
                    : null),
            ],
        ];
    }
}
