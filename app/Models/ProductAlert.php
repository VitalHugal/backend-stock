<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class ProductAlert extends Model
{
    use HasApiTokens, SoftDeletes;
    
    protected $fillable = [
        'fk_product_equipament_id',
        'quantity_min',
        'fk_category_id'
    ];
    protected $table = 'product_alerts';
    protected $dates = ['deleted_at'];

    public function rulesProductAlert()
    {
        return [
            'fk_product_equipament_id' =>  'required|exists:products_equipaments,id',
            'quantity_min' => 'required|integer',
            'fk_category_id' => 'required|exists:category,id',
        ];
    }

    public function feedbackProductAlert()
    {
        return [
            'required' => 'Campo obrigatório.',
            'exists:products_equipaments,id' => 'Produto informado não encontrado, verifique.',
            'integer' => 'Válido apenas valores números inteiro.',
            'required|exists:category,id' => 'Categoria não encontrada, verifique.',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }
    
    public function inputs()
    {
        return $this->belongsTo(Inputs::class, 'fk_product_equipament_id');
    }
}