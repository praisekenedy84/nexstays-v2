<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\HBMS\Models\FolioTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FolioTransaction */
class FolioTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'folio_transaction',
            'attributes' => [
                'transaction_type' => $this->transaction_type,
                'description' => $this->description,
                'amount' => $this->amount,
                'tax_amount' => $this->tax_amount,
                'tax_code' => $this->tax_code,
                'posted_at' => $this->posted_at?->toIso8601String(),
                'voided_at' => $this->voided_at?->toIso8601String(),
            ],
        ];
    }
}
