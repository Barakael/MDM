<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'device_id',
        'push_magic',
        'token',
        'topic',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function summary(): array
    {
        return [
            'topic' => $this->topic,
            'has_token' => filled($this->token),
            'has_push_magic' => filled($this->push_magic),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
