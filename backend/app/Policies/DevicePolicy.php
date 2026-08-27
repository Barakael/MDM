<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->canAccessOrganization($device->organization_id)
            && $user->hasPermission('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('devices.manage');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->canAccessOrganization($device->organization_id)
            && $user->hasPermission('devices.manage');
    }

    public function command(User $user, Device $device): bool
    {
        return $user->canAccessOrganization($device->organization_id)
            && $user->hasPermission('devices.commands');
    }
}
