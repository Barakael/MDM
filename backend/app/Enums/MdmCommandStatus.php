<?php

namespace App\Enums;

enum MdmCommandStatus: string
{
    case Created = 'created';
    case Queued = 'queued';
    case Sent = 'sent';
    case DeviceConnected = 'device_connected';
    case Executed = 'executed';
    case Acknowledged = 'acknowledged';
    case Failed = 'failed';
    case Timeout = 'timeout';
    case Cancelled = 'cancelled';
}
