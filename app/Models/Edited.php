<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edited extends Model
{
    use HasFactory;
    protected $table = 'edited';
    protected $hidden = ['pivot', 'site_id', 'created_at', 'updated_at'];

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_news', 'news_id', 'animal_id')->withPivot('amount');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id');
    }
}
