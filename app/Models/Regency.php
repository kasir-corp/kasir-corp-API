<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    use HasFactory;

    protected $table = "regencies";
    protected $fillable = ["id", "name", "province_id"];
    protected $hidden = ['province_id', 'created_at', 'updated_at'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
