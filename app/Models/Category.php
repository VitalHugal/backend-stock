<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];
    protected $table = 'category';
    protected $dates = ['deleted_at'];


    public function rulesCategory()
    {
        return [
            'name' => 'required|max:40|min:2',
            'description' => 'required|max:100|min:20',
        ];
    }
    public function feedbackCategory()
    {
        return [
            'name.required' => 'Campo nome é obrigatório.',
            'name.max' => 'O campo nome deve conter até 40 caracteres.',
            'name.min' => 'O campo nome deve conter no mínimo 2 caracteres.',
            
            'description.required' => 'Campo descrição é obrigatório.',
            'description.max' => 'O campo deve conter até 100 caracteres.',
            'description.min' => 'O campo nome deve conter no mínimo 20 caracteres.',
        ];
    }

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user', 'fk_category_id', 'fk_user_id');
    }
}