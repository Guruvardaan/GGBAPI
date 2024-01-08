<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoTransferRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'idstore_warehouse_to',
        'idstore_warehouse_from',
        'dispatch_date',
        'dispatched_by',
        'status',
        'created_by',
        'updated_by'
    ];
}
