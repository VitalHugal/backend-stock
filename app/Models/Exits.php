<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exits extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fk_product_equipament_id',
        'fk_user_id',
        'reason_project',
        'observation',
        'quantity',
        'delivery_to',
    ];

    protected $table = 'exits';
    protected $dates = ['deleted_at'];

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    public function rulesExits()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => '|exists:users,id',
            'reason_project' => 'required|max:255',
            'observation' => 'required|max:255',
            'quantity' => 'required|integer|max:1000',
            'withdrawal_date' => '',
            'delivery_to' => 'required',
        ];
    }

    public function feedbackExits()
    {
        return [
            'required' => 'Campo obrigatório.',
            'fk_product_equipament_id.exists' => 'Produto não encontrado, tente novamente.',
            'fk_user_id.exists' => 'Usuário não encontrado, tente novamente.',
            'reason_project.max' => 'O campo deve conter até 255 caracteres.',
            'observation.max' => 'O campo deve conter até 255 caracteres.',
            'integer' => 'Válido apenas números inteiros.',
            'quantity.max' => 'O campo deve ter no máximo 1000',
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