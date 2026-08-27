<?php

namespace App\Services\Mdm;

use App\Models\Device;

interface MdmEngine
{
    public function getDeviceInformation(Device $device): array;

    public function lock(Device $device, array $data = []): array;

    public function enableLostMode(Device $device, array $data = []): array;

    public function disableLostMode(Device $device): array;

    public function erase(Device $device, array $data = []): array;

    public function installProfile(Device $device, string $profile): array;

    public function removeProfile(Device $device, string $profile): array;
}
