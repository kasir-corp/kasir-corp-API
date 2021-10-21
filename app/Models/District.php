<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = "districts";
    protected $fillable = ["id", "name", "regency_id"];
    protected $hidden = ['regency_id', 'created_at', 'updated_at'];
}
