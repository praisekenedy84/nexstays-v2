<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /** Human-friendly label for the notification type. */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'reservation_created' => 'New reservation',
            'check_in'            => 'Check-in',
            'check_out'           => 'Check-out',
            'night_audit'         => 'Night audit',
            default               => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /** Tailwind color classes for the type badge. */
    public function typeColor(): string
    {
        return match ($this->type) {
            'reservation_created' => 'bg-indigo-100 text-indigo-700',
            'check_in'            => 'bg-emerald-100 text-emerald-700',
            'check_out'           => 'bg-amber-100 text-amber-700',
            'night_audit'         => 'bg-slate-100 text-slate-600',
            default               => 'bg-sky-100 text-sky-700',
        };
    }
}
