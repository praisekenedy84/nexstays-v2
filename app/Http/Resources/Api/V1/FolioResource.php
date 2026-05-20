<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\HBMS\Models\Folio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Folio */
class FolioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'folio',
            'attributes' => [
                'folio_number' => $this->folio_number,
                'status' => $this->status,
                'currency' => $this->currency,
                'pre_auth_amount' => $this->pre_auth_amount,
                'settled_amount' => $this->settled_amount,
                'reservation_id' => $this->reservation_id,
            ],
            'relationships' => [
                'transactions' => $this->whenLoaded(
                    'transactions',
                    fn () => FolioTransactionResource::collection($this->transactions)
                ),
                'reservation' => $this->whenLoaded('reservation', fn () => [
                    'id' => $this->reservation->id,
                    'booking_ref' => $this->reservation->booking_ref,
                    'guest_name' => $this->reservation->guest
                        ? trim("{$this->reservation->guest->first_name} {$this->reservation->guest->last_name}")
                        : null,
                ]),
            ],
        ];
    }
}
