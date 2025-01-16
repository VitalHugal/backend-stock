<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class StorageLocation extends Model
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'observation',
    ];

    protected $table = 'storage_locations';
    protected $dates = ['deleted_at'];

    public function rules()
    {
        return [
            'name' => 'required|max:255|min:2',
            'observation' => 'required|max:10000|min:1',
        ];
    }

    public function feedback()
    {
        return  [
            'name.required' => 'Campo nome é obrigatório.',
            'name.max' => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.min' => 'O campo nome deve ter no mínimo 2 caracteres.',

            'observation.required' => 'Campo observação é obrigatório.',
            'observation.max' => 'O campo não aceita mais que 10.000 caracteres.',
            'observation.min' => 'O campo observação deve ter no minímo 1 caractere.',
        ];
    }

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }
}