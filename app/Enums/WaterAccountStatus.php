<?php

namespace App\Enums;

enum WaterAccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Disconnected = 'disconnected';
    case Closed = 'closed';
}
