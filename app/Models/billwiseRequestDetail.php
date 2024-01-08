<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class billwiseRequestDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'idbillwise_requests',
        'idproduct_master',
        'idproduct_batch',
        'quantity',
        'quantity_sent',
        'quantity_received',
        'status',
        'created_by',
        'updated_by'
    ];
}
