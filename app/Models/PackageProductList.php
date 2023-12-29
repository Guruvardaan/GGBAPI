<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageProductList extends Model
{
    use HasFactory;
   
    protected $table = 'package_prod_list';
    protected $primaryKey = 'idpackage_prod_list';
    protected $fillable = ['idpackage_prod_list', 'idpackage', 'idproduct_master', 'quantity', 'is_triggerer_tag_along', 'created_by', 'updated_by', 'status'];
}
