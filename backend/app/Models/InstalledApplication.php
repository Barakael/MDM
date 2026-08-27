<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledApplication extends Model
{
    protected $fillable = [
        'device_id',
        'name',
        'bundle_identifier',
        'version',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
