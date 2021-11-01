<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = "news";
    protected $fillable = ["id", "title", "url", "date", "organization_id", "site_id", "district_id", "regency_id", "province_id", "isTrained", "label"];
    protected $hidden = ['pivot', 'organization_id', 'site_id', 'district_id', 'regency_id', 'province_id', 'created_at', 'updated_at'];

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_news', 'news_id', 'animal_id')->withPivot('amount');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }
}
