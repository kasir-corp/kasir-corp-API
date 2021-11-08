<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edited extends Model
{
    use HasFactory;
    protected $table = 'edited';
    protected $hidden = ['pivot', 'user_id', 'news_id', 'site_id', 'created_at', 'updated_at'];

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'edited_animal', 'news_id', 'animal_id')->withPivot('amount');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id');
    }

    public function regencies()
    {
        return $this->belongsToMany(Regency::class, 'edited_regency', 'news_id', 'regency_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'edited_organization', 'news_id', 'organization_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
