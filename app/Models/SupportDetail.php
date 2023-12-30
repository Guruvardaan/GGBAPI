<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupportImage;

class SupportDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'description',
        'support_id',
        'created_by'
    ];
    
    public function Images()
    {
        return $this->hasMany(SupportImage::class, 'support_detail_id');
    }
}
