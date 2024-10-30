<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = 'category';

    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user', 'fk_category_id', 'fk_user_id');
    }
}