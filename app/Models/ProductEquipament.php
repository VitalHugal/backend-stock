<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEquipament extends Model
{
    protected $fillable = ['name', 'qtn', 'fk_id_category'];
    protected $table = 'users';
    protected $dates = 'deleted_at';

    
}