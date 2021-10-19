<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = "news";
    protected $fillable = ["id", "title", "url", "date", "organization_id", "site_id", "district_id", "regency_id", "province_id", "isTrained", "label"];

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_news', 'animal_id', 'news_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
