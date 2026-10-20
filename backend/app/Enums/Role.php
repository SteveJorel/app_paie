<?php

namespace App\Enums;

enum Role: string
{
    case Client = 'client';
    case Supplier = 'supplier';
    case Admin = 'admin';
    case Finance = 'finance';
}