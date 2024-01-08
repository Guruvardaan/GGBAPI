<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoTransferRequestDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'idauto_transfer_requests',
        'idproduct_master',
        'quantity',
        'quantity_sent',
        'quantity_received',
        'status',
        'created_by',
        'updated_by'
    ];
}
