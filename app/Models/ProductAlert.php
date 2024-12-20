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

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

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
            'fk_product_equipament_id.exists' => 'Produto informado não encontrado, verifique.',
            'integer' => 'Válido apenas números inteiros.',
            'fk_category_id.exists' => 'Categoria não encontrada, verifique.',
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