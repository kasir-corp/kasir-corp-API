<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $table = "sites";
    protected $fillable = ["id", "name"];
    protected $hidden = ['created_at', 'updated_at'];

    public function news()
    {
        return $this->hasMany(News::class);
    }
}
