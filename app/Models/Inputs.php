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

    public function rulesInputs()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'quantity' => 'required|integer',
            'fk_user_id' => 'exists:users,id',
        ];
    }

    public function feedbackInputs()
    {
        return [
            'required' => 'Campo obrigatório.',
            'integer' => 'Válido apenas números inteiros.',
            'exists:products_equipaments,id' => 'Producto informado não existe, verifique.',
            'exists:users,id' => 'Usuario informado não existe, verifique.',
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