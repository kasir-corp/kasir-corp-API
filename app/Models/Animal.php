<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $table = "animals";
    protected $fillable = ["id", "name"];
    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    public function news()
    {
        return $this->belongsToMany(News::class, 'animal_news', 'news_id', 'animal_id');
    }
}
