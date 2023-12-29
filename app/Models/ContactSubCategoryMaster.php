<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubCategoryMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'category_id',
        'status'  // 0-inactive, 1-active
    ];
}
