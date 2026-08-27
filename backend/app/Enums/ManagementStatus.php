<?php

namespace App\Enums;

enum ManagementStatus: string
{
    case Unmanaged = 'unmanaged';
    case Managed = 'managed';
    case Supervised = 'supervised';
    case LostMode = 'lost_mode';
    case Wiped = 'wiped';
}
