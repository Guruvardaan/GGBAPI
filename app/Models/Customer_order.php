<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;
use App\Models\order_detail;
class customer_order extends Model
{
    protected $table = 'customer_order';

    public function orderDetail()
    {
        return $this->hasMany(order_detail::class, 'idcustomer_order');
    }
}
