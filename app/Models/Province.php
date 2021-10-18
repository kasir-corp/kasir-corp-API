<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = "provinces";
    protected $fillables = ["id", "name"];

    public function regencies()
    {
        return $this->hasMany(Regency::class);
    }
}
