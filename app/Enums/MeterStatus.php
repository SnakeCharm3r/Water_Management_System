<?php

namespace App\Enums;

enum MeterStatus: string
{
    case Available = 'available';
    case Installed = 'installed';
    case Removed = 'removed';
    case Faulty = 'faulty';
    case UnderMaintenance = 'under_maintenance';
    case Decommissioned = 'decommissioned';
}
