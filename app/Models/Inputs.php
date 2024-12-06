<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Inputs extends Model
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'fk_product_equipament_id',
        'quantity',
        'fk_user_id',
    ];
    protected $table = 'inputs';
    protected $dates = ['deleted_at'];

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    public function rulesInputs()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'quantity' => 'required|integer|max:10000',
            'fk_user_id' => 'exists:users,id',
        ];
    }

    public function feedbackInputs()
    {
        return [
            'required' => 'Campo obrigatório.',
            'integer' => 'Válido apenas números inteiros.',
            'fk_product_equipament_id.exists' => 'Producto informado não existe, verifique.',
            'fk_user_id.exists' => 'Usuario informado não existe, verifique.',
            'quantity.max' => 'O campo deve ter no máximo 10.000'
        ];
    }

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }
}