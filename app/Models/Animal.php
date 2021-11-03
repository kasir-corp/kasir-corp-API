<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $table = "animals";
    protected $fillable = ["id", "name", "category_id", "scientific_name"];
    protected $hidden = ['pivot', 'created_at', 'updated_at', 'category_id'];

    public function news()
    {
        return $this->belongsToMany(News::class, 'animal_news', 'animal_id', 'news_id')->withPivot('amount');
    }

    public function edited_news()
    {
        return $this->belongsToMany(Edited::class, 'edited_animal', 'animal_id', 'news_id')->withPivot('amount');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
