<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MdmCommandResult extends Model
{
    protected $fillable = [
        'mdm_command_id',
        'status',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
        ];
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(MdmCommand::class, 'mdm_command_id');
    }
}
