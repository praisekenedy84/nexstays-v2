<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioTransaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'folio_id',
        'transaction_type',
        'description',
        'amount',
        'tax_amount',
        'tax_code',
        'reference_id',
        'reference_type',
        'posted_by',
        'posted_at',
        'voided_at',
        'void_reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
