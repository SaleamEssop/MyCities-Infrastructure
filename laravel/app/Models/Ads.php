<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Ads extends Model
{
    use HasFactory;

    protected $fillable = ['ads_category_id', 'name', 'image', 'url', 'price', 'priority', 'description'];

    public function category()
    {
        return $this->belongsTo(AdsCategory::class, 'ads_category_id');
    }

    public function getImageAttribute($value)
    {   
        if(!empty($value)){
            return URL::to(Storage::url($value));
        }else{
            return '';
        }   
    }
}
