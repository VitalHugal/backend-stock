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
        'fk_category_id',
        'quantity_min',
        'observation',
        'expiration_date',
        'is_grup',
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
            'observation' => 'max:50000',
            'expiration_date' => 'required|boolean:0,1',
            'is_grup' => 'required|boolean:0,1',
            // 'list_products' => 'nullable|array|exists:products_equipaments,id',
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

            'observation.max' => 'O campo observação deve ter no máximo 50.000 caracteres.',

            'expiration_date.required' => 'O campo data de validade é obrigatório.',
            'expiration_date.boolean' => 'Válido apenas 0 ou 1 nesse campo.',

            'is_grup.required' => 'O campo "é grupo" é obrigatório.',
            'is_grup.boolean' => 'Válido apenas 0 ou 1 nesse campo.',

            // 'list_products.array' => 'O campo lista de produtos precisa ser um array ex: [1,2,3]',
            // 'list_products.exists' => 'Produto(s) não encontrado(s).',

        ];
    }
    public function rulesProductEquipamentsIsGrup()
    {
        return [
            'name' => 'required|max:255|min:2',
            'quantity_min' => 'nullable|integer|max:10000|',
            'fk_category_id' => 'required|exists:category,id',
            'observation' => 'max:50000',
            'expiration_date' => 'required|boolean:0,1',
            'is_grup' => 'required|boolean:0,1',
            'list_products' => 'nullable|exists:products_equipaments,id',
        ];
    }

    public function feedbackProductEquipamentsIsGrup()
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

            'observation.max' => 'O campo observação deve ter no máximo 50.000 caracteres.',

            'expiration_date.required' => 'O campo data de validade é obrigatório.',
            'expiration_date.boolean' => 'Válido apenas 0 ou 1 nesse campo.',

            'is_grup.required' => 'O campo "é grupo" é obrigatório.',
            'is_grup.boolean' => 'Válido apenas 0 ou 1 nesse campo.',

            'list_products.array' => 'O campo lista de produtos precisa ser um array ex: [1,2,3]',
            'list_products.exists' => 'Produto(s) não encontrado(s).',
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

    // Produtos que este grupo engloba
    public function components()
    {
        return $this->hasManyThrough(
            ProductEquipament::class,
            'product_groups',
            'group_product_id', // Chave estrangeira para o grupo
            'id', // Chave primária na tabela `products`
            'id', // Chave primária no modelo atual
            'component_product_id' // Chave estrangeira para os componentes
        );
    }

    // O grupo ao qual este produto pertence
    public function group()
    {
        return $this->belongsToMany(
            ProductEquipament::class,
            'product_groups',
            'component_product_id', // Chave estrangeira para o componente
            'group_product_id' // Chave estrangeira para o grupo
        );
    }
}