<?php

namespace App\Enums;

enum MdmCommandType: string
{
    case DeviceInformation = 'DeviceInformation';
    case DeviceLock = 'DeviceLock';
    case EnableLostMode = 'EnableLostMode';
    case DisableLostMode = 'DisableLostMode';
    case EraseDevice = 'EraseDevice';
    case InstallProfile = 'InstallProfile';
    case RemoveProfile = 'RemoveProfile';
}
