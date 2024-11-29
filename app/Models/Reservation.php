<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Reservation extends Model
{
    use HasApiTokens, SoftDeletes;

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
    protected $dates = ['deleted_at'];


    public static function filterReservations($request)
    {
        $query = self::with(['productEquipament.category', 'user', 'userFinished']);

        // Aplica o filtro apenas se o parâmetro 'reservation_finished' estiver na requisição
        if ($request->has('reservation_finished')) {
            $query->where('reservation_finished', $request->input('reservation_finished'));
        }

        return $query->get()->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'fk_user_id_create' => $reservation->fk_user_id,
                'name_user_create' => $reservation->user->name ?? null,
                'reason_project' => $reservation->reason_project,
                'observation' => $reservation->observation,
                'quantity' => $reservation->quantity,
                'withdrawal_date' => $reservation->withdrawal_date,
                'return_date' => $reservation->return_date,
                'delivery_to' => $reservation->delivery_to,
                'reservation_finished' => $reservation->reservation_finished,
                'date_finished' => $reservation->date_finished,
                'fk_user_id_finished' => $reservation->fk_user_id_finished,
                'name_user_finished' => $reservation->userFinished->name ?? null,
                'product_name' => $reservation->productEquipament->name ?? null,
                'category_name' => $reservation->productEquipament->category->name ?? null,
            ];
        });
    }

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
            'reservation_finished' => 'required|boolean|',
            'date_finished' => '',
            'fk_user_id_finished' => 'exists:users,id'
        ];
    }
    public function feedbackFinishedReservation()
    {
        return [
            'required' => 'Campo obrigatório.',
            'boolean' => 'Válido apenas "1" para esse campo.',
            'exists:users,id' => 'Usuário não encontrado, tente novamente.',
        ];
    }

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    public function productEquipament()
    {
        return $this->belongsTo(ProductEquipament::class, 'fk_product_equipament_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }

    public function userFinished()
    {
        return $this->belongsTo(User::class, 'fk_user_id_finished');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }
}