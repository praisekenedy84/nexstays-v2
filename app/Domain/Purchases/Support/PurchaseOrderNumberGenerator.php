<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Support;

use App\Domain\Purchases\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'PO-'.now()->format('Y').'-';

        return DB::transaction(function () use ($prefix) {
            $last = PurchaseOrder::query()
                ->where('po_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('po_number')
                ->value('po_number');

            $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

            return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
