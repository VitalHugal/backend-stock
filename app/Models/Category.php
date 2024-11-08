<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $fillable = [
        'name',
        'description'
    ];
    protected $table = 'category';
    protected $dates = 'deleted_at';

    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user', 'fk_category_id', 'fk_user_id');
    }

    public function rulesCategory()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'required|max:65000',
        ];
    }
    public function feedbackCategory()
    {
        return [
            'required' => 'Campo obrigatório.',
            'max:255' => 'O campo deve conter até 255 caracteres.',
            'max:65000' => 'O campo deve conter até 65.000 caracteres.',
        ];
    }
}