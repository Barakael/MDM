<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\ManagementStatus;
use App\Enums\MdmEngineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'organization_id',
        'device_name',
        'platform',
        'model',
        'serial_number',
        'udid',
        'imei',
        'os_version',
        'supervised',
        'management_status',
        'enrollment_status',
        'mdm_engine',
        'is_online',
        'last_contact_at',
        'raw_info',
    ];

    protected function casts(): array
    {
        return [
            'supervised' => 'boolean',
            'is_online' => 'boolean',
            'last_contact_at' => 'datetime',
            'raw_info' => 'array',
            'management_status' => ManagementStatus::class,
            'enrollment_status' => EnrollmentStatus::class,
            'mdm_engine' => MdmEngineType::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(MdmCommand::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeviceEvent::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InstalledApplication::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(DeviceProfile::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(DeviceCertificate::class);
    }

    public function resolvedEngine(): MdmEngineType
    {
        return $this->mdm_engine
            ?? MdmEngineType::from(config('mdm.engine', 'nano'));
    }
}
