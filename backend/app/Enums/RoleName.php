<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super_Admin';
    case Admin = 'Admin';
    case DeviceManager = 'Device_Manager';
    case Auditor = 'Auditor';
}
