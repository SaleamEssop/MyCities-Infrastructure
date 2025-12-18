<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','parent_id'];

    public function ads()
    {
        return $this->hasMany(Ads::class)->orderByRaw('ISNULL(priority), priority ASC');
    }
    public function childs() {
        return $this->hasMany(self::class,'parent_id','id');
    }
    public function child_display() {
        return $this->hasOne(self::class,'id','parent_id');
    }
   
}
