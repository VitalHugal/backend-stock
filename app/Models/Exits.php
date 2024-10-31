<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exits extends Model
{
    protected $fillable = ['name', 'description'];
    protected $table = 'category';
    protected $dates = 'deleted_at';
}