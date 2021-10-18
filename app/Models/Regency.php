<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    use HasFactory;

    protected $table = "regencies";
    protected $fillables = ["id", "name", "province_id"];

    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
