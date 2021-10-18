<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $table = "animals";
    protected $fillable = ["id", "name"];

    public function news()
    {
        return $this->belongsToMany(News::class, 'animal_news', 'news_id', 'animal_id');
    }
}
