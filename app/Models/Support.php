<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'title',
        'description',
        'category',
        'idcustomer',
        'idcustomer_order',
        'status'  // 0-open, 1-closed
    ];
}
