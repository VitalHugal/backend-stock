<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'fk_product_equipament_id',
        'fk_user_id',
        'reason_project',
        'observation',
        'quantity',
        'withdrawal_date',
        'return_date',
        'delivery_to',
        'reservation_finished',
        'date_finished',
        'fk_user_id_finished',
    ];
    protected $table = 'reservations';
    protected $dates = 'deleted_at';

    public function rulesReservation()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => 'required|exists:users,id',
            'reason_project' => 'required|max:255',
            'observation' => 'required|max:255',
            'quantity' => 'required|integer',
            'withdrawal_date' => 'required',
            'delivery_to' => 'required',
            'return_date' => 'required',
            'reservation_finished' => '',
            'date_finished' => '',
            'fk_user_id_finished' => '',
        ];
    }

    public function feedbackReservation()
    {
        return [
            'required' => 'Campo obrigatório.',
            'exists:product_equipament,id' => 'Produto não encontrado, tente novamente.',
            'exists:users,id' => 'Usuário não encontrado, tente novamente.',
            'max:255' => 'O campo deve conter até 255 caracteres.',
            'integer' => 'Válido apenas números inteiros.',
        ];
    }

    public function rulesFinishedReservation()
    {
        return [
            'reservation_finished' => 'required|in:true|',
            'date_finished' => 'required',
            'fk_user_id_finished' => 'exists:users,id'
        ];
    }
    public function feedbackFinishedReservation()
    {
        return [
            'required' => 'Campo obrigatório.',
            'in:true' => 'Válido apenas "true" para esse campo.',
            'exists:users,id' => 'Usuário não encontrado, tente novamente.',
        ];
    }

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }
}