<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingChargeMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'shipping_charge',
        'order_amount',
        'title',
        'created_by',
        'updated_by',
        'status'  // 0-inactive, 1-active
    ];
}
