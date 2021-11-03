<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $hidden = ['created_at', 'updated_at'];

    public function animals()
    {
        return $this->hasMany(Animal::class, 'category_id');
    }
}
