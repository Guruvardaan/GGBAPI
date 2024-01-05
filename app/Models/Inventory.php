<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $table = 'inventory';
    protected $fillable = [
        'idstore_warehouse',
		'idproduct_master',
		'selling_price',
		'purchase_price',
		'mrp',
		'discount',
		'instant_discount_percent',
		'quantity',
		'product',
		'copartner',
		'land',
		'only_online',
		'only_offline',
		'listing_type',
        'created_by',
		'updated_by',
        'status'  
    ];
}
