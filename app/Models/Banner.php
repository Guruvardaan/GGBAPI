<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'title',
        'sub_title',
        'link',
        'banner_type',
        'type',
        'type_id',
        'status'  // 0 for inactive, 1 for active
    ];
}
