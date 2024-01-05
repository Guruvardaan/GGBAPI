<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;
    protected $table = 'product_batch';
    protected $fillable = [
        'idproduct_master',
        'idstore_warehouse',
        'name',
        'purchase_price',
        'selling_price',
        'mrp',
        'product',
        'copartner',
		'land',
		'discount',
		'quantity',
		'expiry',
	    'created_by',
		'updated_by',
        'status'  
    ];
}
