<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportCategoryMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'status'  // 0-inactive, 1-active
    ];
}
