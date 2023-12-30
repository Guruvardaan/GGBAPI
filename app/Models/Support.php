<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupportDetail;

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

    public function Details()
    {
        return $this->hasMany(SupportDetail::class, 'support_id')->with('images');
    }
}
