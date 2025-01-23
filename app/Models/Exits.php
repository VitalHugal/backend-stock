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
        'fk_inputs_id',
        'discarded',
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
            'reason_project' => 'required|max:255|min:5',
            'observation' => 'required|max:255|min:2',
            'quantity' => 'required|integer|max:1000',
            'withdrawal_date' => '',
            'delivery_to' => 'required',
            'fk_inputs_id' => 'required|exists:inputs,id',
            'discarded' => '|boolean:0,1',
        ];
    }

    public function feedbackExits()
    {
        return [
            'reason_project.required' => 'O campo razão é obrigatório.',
            'reason_project.max' => 'O campo razão deve conter no máximo 255 caracteres.',
            'reason_project.min' => 'O campo razão deve conter no mínimo 5 caracteres.',

            'observation.required' => 'O campo observação é obrigatório.',
            'observation.max' => 'O campo observação deve conter no máximo 255 caracteres.',
            'observation.min' => 'O campo observação deve conter no mínimo 2 caracteres.',

            'quantity.required' => 'O campo quantidate é obrigatório.',
            'quantity.max' => 'O campo quantidate deve conter no máximo 1000',
            'quantity.integer' => 'Válido apenas números inteiros.',

            'delivery_to.required' => 'Campo entregue para é obrigatório.',

            'fk_product_equipament_id.exists' => 'Produto não encontrado, tente novamente.',

            'fk_user_id.exists' => 'Usuário não encontrado, tente novamente.',

            'fk_inputs_id.exists' => 'Entrada não encontrada, tente novamente.',

            'fk_inputs_id.required' => 'Campo entrada é obrigatório.',

            // 'discarded.required' => 'O campo "descarte" é obrigátorio.',
            'discarded.boolean' => 'Válido apenas 0 ou 1 nesse campo.',
            'discarded.required' => 'Campo descarte é obrigatório.',
        ];
    }

    public function rulesExitsDiscardedExpirationOne()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => '|exists:users,id',
            'reason_project' => '|max:255|min:5',
            'observation' => 'required|max:255|min:2',
            'quantity' => 'required|integer|max:1000',
            'withdrawal_date' => '',
            'delivery_to' => '',
            'fk_inputs_id' => 'required|exists:inputs,id',
            'discarded' => 'required|boolean:0,1',
        ];
    }

    public function feedbackExitsDiscardedOne()
    {
        return [
            'reason_project.required' => 'O campo razão é obrigatório.',
            'reason_project.max' => 'O campo razão deve conter no máximo 255 caracteres.',
            'reason_project.min' => 'O campo razão deve conter no mínimo 5 caracteres.',

            'observation.required' => 'O campo observação é obrigatório.',
            'observation.max' => 'O campo observação deve conter no máximo 255 caracteres.',
            'observation.min' => 'O campo observação deve conter no mínimo 2 caracteres.',

            'quantity.required' => 'O campo quantidate é obrigatório.',
            'quantity.max' => 'O campo quantidate deve conter no máximo 1000',
            'quantity.integer' => 'Válido apenas números inteiros.',

            'delivery_to.required' => 'Campo entregue para é obrigatório.',

            'fk_product_equipament_id.exists' => 'Produto não encontrado, tente novamente.',

            'fk_user_id.exists' => 'Usuário não encontrado, tente novamente.',

            'fk_inputs_id.exists' => 'Entrada não encontrada, tente novamente.',

            // 'discarded.required' => 'O campo "descarte" é obrigátorio.',
            'discarded.boolean' => 'Válido apenas 0 ou 1 nesse campo.',
            'discarded.required' => 'Campo descarte é obrigatório.',
        ];
    }
    public function rulesExitsExpirationDateZeroDiscardedZero()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => '|exists:users,id',
            'reason_project' => 'required|max:255|min:5',
            'observation' => 'required|max:255|min:2',
            'quantity' => 'required|integer|max:1000',
            'withdrawal_date' => '',
            'delivery_to' => 'required',
            'fk_inputs_id' => '',
            'discarded' => 'required|boolean:0,1',
        ];
    }

    public function feedbackExitsExpirationDateZeroDiscardedZero()
    {
        return [
            'reason_project.required' => 'O campo razão é obrigatório.',
            'reason_project.max' => 'O campo razão deve conter no máximo 255 caracteres.',
            'reason_project.min' => 'O campo razão deve conter no mínimo 5 caracteres.',

            'observation.required' => 'O campo observação é obrigatório.',
            'observation.max' => 'O campo observação deve conter no máximo 255 caracteres.',
            'observation.min' => 'O campo observação deve conter no mínimo 2 caracteres.',

            'quantity.required' => 'O campo quantidate é obrigatório.',
            'quantity.max' => 'O campo quantidate deve conter no máximo 1000',
            'quantity.integer' => 'Válido apenas números inteiros.',

            'delivery_to.required' => 'Campo entregue para é obrigatório.',

            'fk_product_equipament_id.exists' => 'Produto não encontrado, tente novamente.',

            'fk_user_id.exists' => 'Usuário não encontrado, tente novamente.',

            // 'fk_inputs_id.exists' => 'Entrada não encontrada, tente novamente.',

            // 'discarded.required' => 'O campo "descarte" é obrigátorio.',
            'discarded.boolean' => 'Válido apenas 0 ou 1 nesse campo.',
            'discarded.required' => 'Campo descarte é obrigatório.',
        ];
    }

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }
    public function input()
    {
        return $this->belongsTo(Inputs::class, 'fk_inputs_id');
    }
}