<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
}
