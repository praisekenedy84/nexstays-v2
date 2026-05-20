<?php

declare(strict_types=1);

namespace App\Domain\Till\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TillMovement extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'till_session_id', 'movement_type', 'amount', 'currency',
        'reference_id', 'reference_type', 'description', 'performed_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TillSession::class, 'till_session_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
