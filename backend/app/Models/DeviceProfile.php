<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceProfile extends Model
{
    protected $fillable = [
        'device_id',
        'configuration_profile_id',
        'profile_identifier',
        'display_name',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
