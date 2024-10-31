<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEquipament extends Model
{
    protected $fillable = ['name', 'quantity', 'fk_category_id', 'quantity_min'];
    protected $table = 'products_equipaments';
    protected $dates = 'deleted_at';

    public function rulesProductEquipamentos()
    {
        return [
            'name' => 'required|max:255',
            'quantity' => 'required|integer|',
            'quantity_min' => 'required|integer|',
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

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }
}