<?php

declare(strict_types=1);

namespace App\Http\Rules;

use App\Domain\Shared\Models\Order;
use App\Domain\Till\Services\TillSessionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveTillSessionForOutlet implements ValidationRule
{
    public function __construct(
        private readonly ?Order $order = null,
        private readonly ?string $outletId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $outletId = $this->outletId ?? $this->order?->outlet_id;

        $session = app(TillSessionService::class)->activeForOutlet($outletId);

        if ($session === null || $session->id !== $value) {
            $fail('A valid active till session is required for cash payments at this outlet.');
        }
    }
}
