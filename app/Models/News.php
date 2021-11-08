<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = "news";
    protected $fillable = ["id", "title", "url", "date", "site_id", "is_trained", "label"];
    protected $hidden = ['pivot', 'site_id', 'district_id', 'created_at', 'updated_at'];

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_news', 'news_id', 'animal_id')->withPivot('amount');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'news_organization', 'news_id', 'organization_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function regencies()
    {
        return $this->belongsToMany(Regency::class, 'news_regency', 'news_id', 'regency_id');
    }

    public function edited()
    {
        return $this->hasMany(Edited::class, 'news_id');
    }
}
