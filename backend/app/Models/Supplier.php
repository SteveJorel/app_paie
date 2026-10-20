<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'country_code',
        'category',
        'settlement_currency',
        'contact_email',
        'contact_phone',
        'owner_user_id',
    ];
}