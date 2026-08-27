<?php

namespace App\Models;

use App\Enums\MdmCommandStatus;
use App\Enums\MdmCommandType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MdmCommand extends Model
{
    protected $fillable = [
        'device_id',
        'issued_by',
        'command_uuid',
        'command_type',
        'engine',
        'payload',
        'status',
        'queued_at',
        'sent_at',
        'completed_at',
        'error',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'command_type' => MdmCommandType::class,
            'status' => MdmCommandStatus::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(MdmCommandResult::class);
    }
}
