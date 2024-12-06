<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class ProductEquipament extends Model
{
    use SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name',
        // 'quantity',
        'fk_category_id',
        'quantity_min'
    ];
    protected $table = 'products_equipaments';
    protected $dates = ['deleted_at'];

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    public function rulesProductEquipamentos()
    {
        return [
            'name' => 'required|max:255|min:2',
            'quantity_min' => 'required|integer|max:10000|min:1',
            'fk_category_id' => 'required|exists:category,id',
        ];
    }

    public function feedbackProductEquipaments()
    {
        return [
            'name.required' => 'Campo nome é obrigatório.',
            'name.max' => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.min' => 'O campo nome deve ter no mínimo 2 caracteres.',
            
            'quantity_min.required' => 'Campo qtd. mínima é obrigatório.',
            'quantity_min.integer' => 'Válido apenas números inteiros.',
            'quantity_min.max' => 'O campo qtd. mínima deve ter no máximo 10.000.',
            'quantity_min.min' => 'O campo qtd. mínima deve ter no mínimo 1.',
            
            'fk_category_id.required' => 'Campo setor é obrigatório.',
            'fk_category_id.exists' => 'Categoria não encontrada, verifique.',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function productsEquipaments()
    {
        return $this->hasMany(ProductEquipament::class, 'fk_exit_id');
    }

    public function inputs()
    {
        return $this->belongsTo(Inputs::class, 'fk_product_equipament_id');
    }
}