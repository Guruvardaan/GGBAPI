<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;
use App\Models\ContactSubCategoryMaster;

class ContactCategoryMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'status'  // 0-inactive, 1-active
    ];

    public function subCategory()
    {
        return $this->hasMany(ContactSubCategoryMaster::class, 'category_id');
    }
}
