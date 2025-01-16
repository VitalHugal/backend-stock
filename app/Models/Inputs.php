<?php

namespace App\Models;

use DateTime;
use Exception;
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

        'fk_storage_locations_id',
        'date_of_manufacture',
        'expiration_date',
        'status',
        'alert',
        'date_of_alert',

        'quantity_active'
    ];
    protected $table = 'inputs';
    protected $dates = ['deleted_at'];

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    function getFormatteDateofManufactureOrExpiration($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0];
    }

    // function daysUntilDate($targetDate) {

    //     $currentDate = date('d/m/Y');

    //     $currentDate = new DateTime();
    //     $targetDate = DateTime::createFromFormat('d/m/Y', $targetDate);

    //     if (!$targetDate) {
    //         throw new Exception('Formato de data inválido. Use DD/MM/YYYY.');
    //     }
    //     $interval = $currentDate->diff($targetDate);

    //     return $targetDate > $currentDate ? $interval->days : 0;
    // }

    function daysUntilDate($targetDate)
    {
        // Obtém a data atual no formato 'd/m/Y'
        $currentDate = date('d/m/Y');

        // Converte ambas as datas em timestamps
        $currentTimestamp = strtotime(str_replace('/', '-', $currentDate));
        $targetTimestamp = strtotime(str_replace('/', '-', $targetDate));

        // Verifica se a data de destino é válida
        if (!$targetTimestamp) {
            throw new Exception('Formato de data inválido. Use DD/MM/YYYY.');
        }

        // Calcula a diferença em segundos e converte para dias
        $differenceInSeconds = $targetTimestamp - $currentTimestamp;
        $daysRemaining = ceil($differenceInSeconds / (60 * 60 * 24));

        // Retorna os dias restantes, ou 0 se a data já passou
        return $daysRemaining > 0 ? $daysRemaining : 0;
    }

    public function rulesInputs()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => 'exists:users,id',
            'quantity' => 'required|integer|max:10000|min:1',
            'fk_storage_locations_id' => 'required|exists:storage_locations,id'
        ];
    }

    public function feedbackInputs()
    {
        return [
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'quantity.integer' => 'Válido apenas números inteiros.',
            'quantity.max' => 'O campo quantidade deve ter no máximo 10.000.',
            'quantity.min' => 'O campo quantidade deve ter no mínimo 1.',

            'fk_product_equipament_id.exists' => 'Produto informado não existe, verifique.',
            'fk_product_equipament_id.required' => 'Produto é obrigatório.',

            'fk_user_id.exists' => 'Usuário informado não existe, verifique.',

            'fk_storage_locations_id.exists' => 'Local de armazenamento informado não existe, verifique.',
            'fk_storage_locations_id.required' => 'Local de armazenamento é obrigatório.'
        ];
    }

    public function rulesInputsDateOfManufacture()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => 'exists:users,id',
            'quantity' => 'required|integer|max:10000|min:1',
            'fk_storage_locations_id' => 'required|exists:storage_locations,id',
            'date_of_manufacture' => 'required|min:10|max:20',
        ];
    }

    public function feedbackInputsDateOfManufacture()
    {
        return [
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'quantity.integer' => 'Válido apenas números inteiros.',
            'quantity.max' => 'O campo quantidade deve ter no máximo 10.000.',
            'quantity.min' => 'O campo quantidade deve ter no mínimo 1.',

            'fk_product_equipament_id.exists' => 'Produto informado não existe, verifique.',
            'fk_product_equipament_id.required' => 'Produto é obrigatório.',

            'fk_user_id.exists' => 'Usuário informado não existe, verifique.',

            'fk_storage_locations_id.exists' => 'Local de armazenamento informado não existe, verifique.',
            'fk_storage_locations_id.required' => 'Local de armazenamento é obrigatório.',

            'date_of_manufacture.required' => 'O campo data de fabricação é obrigatório.',
            'date_of_manufacture.min' => 'O campo data de fabricação deve conter no minimo 10 caracteres.',
            'date_of_manufacture.max' => 'O campo data de fabricação é obrigatório deve conter no máximo 20 caracteres..',
        ];
    }

    public function rulesInputsExpirationDate()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => 'exists:users,id',
            'quantity' => 'required|integer|max:10000|min:1',
            'fk_storage_locations_id' => 'required|exists:storage_locations,id',
            'date_of_manufacture' => 'required|min:10|max:20',
            'expiration_date' => 'required|min:10|max:20',
            'status' => '',
            'alert' => 'required|integer|max:10000|min:1',
            'date_of_alert' => '',
        ];
    }

    public function feedbackInputsExpirationDate()
    {
        return [
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'quantity.integer' => 'Válido apenas números inteiros.',
            'quantity.max' => 'O campo quantidade deve ter no máximo 10.000.',
            'quantity.min' => 'O campo quantidade deve ter no mínimo 1.',

            'fk_product_equipament_id.exists' => 'Produto informado não existe, verifique.',
            'fk_product_equipament_id.required' => 'Produto é obrigatório.',

            'fk_user_id.exists' => 'Usuário informado não existe, verifique.',

            'fk_storage_locations_id.exists' => 'Local de armazenamento informado não existe, verifique.',
            'fk_storage_locations_id.required' => 'Local de armazenamento é obrigatório.',

            'date_of_manufacture.required' => 'O campo data de fabricação é obrigatório.',
            'date_of_manufacture.min' => 'O campo data de fabricação deve conter no minimo 10 caracteres.',
            'date_of_manufacture.max' => 'O campo data de fabricação deve conter no máximo 20 caracteres.',

            'expiration_date.required' => 'O campo data de validade é obrigatório.',
            'expiration_date.min' => 'O campo data de validade deve conter no minimo 10 caracteres.',
            'expiration_date.max' => 'O campo data de validade deve conter no máximo 20 caracteres.',

            'alert.required' => 'O campo tempo para alerta é obrigatório.',
            'alert.integer' => 'Válido apenas números inteiros.',
            'alert.max' => 'O campo tempo para alerta deve ter no máximo 10.000.',
            'alert.min' => 'O campo tempo para alerta deve ter no mínimo 1.',
        ];
    }


    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }

    public function storage_location()
    {
        return $this->belongsTo(StorageLocation::class, 'fk_storage_locations_id');
    }
}