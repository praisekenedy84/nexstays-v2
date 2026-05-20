<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Till\Models\TillSession */
class TillSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'outlet_id' => $this->outlet_id,
            'status' => $this->status,
            'float_amount' => $this->float_amount,
            'currency' => $this->currency,
            'system_cash' => $this->system_cash,
            'declared_cash' => $this->declared_cash,
            'over_short' => $this->over_short,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
