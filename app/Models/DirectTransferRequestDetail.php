<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectTransferRequestDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'iddirect_transfer_requests',
        'idstore_warehouse_to',
        'idproduct_master',
        'quantity',
        'quantity_sent',
        'quantity_received',
        'status',
        'created_by',
        'updated_by'
    ];
}
