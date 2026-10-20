<?php

namespace App\Enums;

enum SupplierStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}