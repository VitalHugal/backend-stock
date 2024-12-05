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
            'name' => 'required|max:255',
            // 'quantity' => 'required|integer|',
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

    public function productsEquipaments()
    {
        return $this->hasMany(ProductEquipament::class, 'fk_exit_id');
    }

    public function inputs()
    {
        return $this->belongsTo(Inputs::class, 'fk_product_equipament_id');
    }
}