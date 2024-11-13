<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fk_category_id',
        'fk_user_id'
    ];
    protected $table = 'category_user';
    protected $dates = ['deleted_at'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }
}