<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEquipament extends Model
{
    protected $fillable = ['name', 'qtn', 'fk_category_id'];
    protected $table = 'users';
    protected $dates = 'deleted_at';

    public function rulesProductEquipamentos()
    {
        return [
            'name' => 'required|max:255',
            'qtn' => 'required|integer',
            'fk_category_id' => 'required|exists:category,id',
        ];
    }

    public function feedbackProductEquipaments()
    {
        return [
            'required' => 'Campo obrigatório.',
            'max:255' => 'O campo deve ter no máximo 255 caracteres.',
            'integer' => 'Válido apenas números inteiros.',
            'exists' => 'Categoria não encontrada verifique.',
        ];
    }
}