<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryPaymentMethod extends Model
{
    protected $fillable = ['country_code', 'payment_method_code', 'status'];
}
