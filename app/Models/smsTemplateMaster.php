<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class smsTemplateMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'body',
        'created_by',
        'updated_by',
        'status'  // 0-inactive, 1-active
    ];
}
