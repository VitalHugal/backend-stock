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
        'withdrawal_date',
        'delivery_to',
    ];

    protected $table = 'exits';
    protected $dates = ['deleted_at'];

    public function rulesExits()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => 'required|exists:users,id',
            'reason_project' => 'required|max:255',
            'observation' => 'required|max:255',
            'quantity' => 'required|integer',
            'withdrawal_date' => 'required',
            'delivery_to' => 'required',
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

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }
}