<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'image',
        'support_id',
        'support_detail_id'
    ];
}
