<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    use HasFactory;

    protected $table = "regencies";
    protected $fillable = ["id", "name", "province_id"];
    protected $hidden = ['pivot', 'province_id', 'created_at', 'updated_at'];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function news()
    {
        return $this->belongsToMany(News::class, 'news_regency', 'regency_id', 'news_id');
    }
}
