<?php

namespace App\Enums;

enum MeterInstallationStatus: string
{
    case Active = 'active';
    case Removed = 'removed';
    case Replaced = 'replaced';
}
