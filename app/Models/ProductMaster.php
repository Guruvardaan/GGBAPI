<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMaster extends Model
{
    use HasFactory;
    protected $table = 'product_master';
    protected $fillable = [
        'name',
        'barcode',
        'sgst',
        'cgst',
        'description',
        'idbrand',
        'idcategory',
        'idsub_category',
        'idsub_sub_category',
        'status',
        'manufacturer',
        'shelf_life',
        'unit',
        'packaging_type',
        'ingredients'
    ];
}
