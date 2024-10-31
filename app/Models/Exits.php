<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exits extends Model
{
    protected $fillable = ['fk_product_equipament_id' => 'fk_user_id', 'reason/project', 'observation', 'quantity', 'withdrawal_date', 'withdrawal_name_user'];
    protected $table = 'exits';
    protected $dates = 'deleted_at';

    public function rulesExits()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:product_equipament,id',
            'fk_user_id' => 'required|exists:users,id',
            'reason/project' => 'required|max:255',
            'observation' => 'required|max:255',
            'quantity' => 'required|integer',
            'withdrawal_date' => 'required|',
            'withdrawal_name_user' => 'required',
        ];
    }

    public function feedbackExits()
    {
        return [
            'required' => 'Campo obrigatório.',
            'exists:product_equipament,id' => 'Produto não encontrado, tente novamente.',
            'exists:users,id' => 'Usuário não encontrado, tente novamente.',
            'max:255' => 'O campo deve conter até 255 caracteres.',
            'integer' => 'Válido apenas números inteiros.',
        ];
    }
}