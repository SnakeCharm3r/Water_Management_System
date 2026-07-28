<?php

namespace App\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Business = 'business';
    case Institution = 'institution';
    case Government = 'government';
}
