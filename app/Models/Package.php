<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $table = 'package';
    protected $primaryKey = 'idpackage';
    protected $fillable = ['idpackage', 'name', 'idpackage_master', 'idstore_warehouse', 'applicable_on', 'frequency', 'base_trigger_amount', 'additional_tag_amount', 'bypass_make_gen', 'valid_from', 'valid_till', 'created_by', 'updated_by', 'status'];
}
