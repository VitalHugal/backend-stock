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
        'status',
        'fk_user_id_finished',
    ];
    protected $table = 'reservations';
    protected $dates = ['deleted_at'];


    public static function filterReservations($request)
    {
        $query = self::with(['productEquipament.category', 'user', 'userFinished']);

        // Filtro por 'reservation_finished'
        if ($request->has('reservation_finished') && $request->input('reservation_finished') != '') {
            if ($request->input('reservation_finished') == 'true') {
                $query->where('reservation_finished', '1');
            } elseif ($request->input('reservation_finished') == 'false') {
                $query->where('reservation_finished', '0');
            }
        }

        // Paginação
        $resultSearch = $query->paginate(10);

        // Mapeamento dos resultados mantendo a paginação
        $resultSearch->setCollection(
            $resultSearch->getCollection()->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                        'fk_user_id_create' => $reservation->fk_user_id,
                        'name_user_create' => $reservation->user->name ?? null,
                        'reason_project' => $reservation->reason_project,
                        'observation' => $reservation->observation,
                        'quantity' => $reservation->quantity,
                        'withdrawal_date' => $this->reservation->getFormattedDate($reservation, 'withdrawal_date'),
                        'return_date' => $this->reservation->getFormattedDate($reservation, 'return_date'),
                        'delivery_to' => $reservation->delivery_to,
                        'status' => $reservation->status,
                        'reservation_finished' => $reservation->reservation_finished,
                        'date_finished' => $reservation->date_finished
                            ? $this->reservation->getFormattedDate($reservation, 'date_finished')
                            : null,
                        'fk_user_id_finished' => $reservation->fk_user_id_finished,
                        'name_user_finished' => $reservation->userFinished->name ?? null,
                        'product_name' => $reservation->productEquipament->name ?? null,
                        'id_product' => $reservation->productEquipament->id ?? null,
                        // 'category_name' => $reservation->productEquipament->category->name ?? null,
                        'category_name' => $reservation->productEquipament->category->trashed()
                            ? $reservation->productEquipament->category->name . ' (Deletado)' // Se deletado, adiciona "(Deletado)"
                            : $reservation->productEquipament->category->name ?? null,
                        'created_at' => $this->reservation->getFormattedDate($reservation, 'created_at'),
                        'updated_at' => $this->reservation->getFormattedDate($reservation, 'updated_at'),
                ];
            })
        );

        return $resultSearch;
    }

    ///////////////////////////////////////////////////
    public function rulesReservation()
    {
        return [
            'fk_product_equipament_id' => 'required|exists:products_equipaments,id',
            'fk_user_id' => '|exists:users,id',
            'reason_project' => 'required|max:255|min:5',
            'observation' => 'required|max:255|min:2',
            'quantity' => 'required|integer|max:1000',
            'withdrawal_date' => '',
            'delivery_to' => 'required',
            'return_date' => 'required',
            'reservation_finished' => '',
            'date_finished' => '',
            'status' => '',
            'fk_user_id_finished' => '',
        ];
    }

    public function feedbackReservation()
    {
        return [
            'observation.required' => 'Campo observação é obrigatório.',
            'observation.max' => 'O campo deve conter até 255 caracteres.',
            'observation.min' => 'O campo observação deve conter no mínimo 2 caracteres.',

            'reason_project.required' => 'Campo razão é obrigatório.',
            'reason_project.max' => 'O campo deve conter até 255 caracteres.',
            'reason_project.min' => 'O campo deve conter no mínimo 5 caracteres.',

            'quantity.required' => 'Campo quantidate é obrigatório.',
            'quantity.max' => 'O campo deve ter no máximo 1000',
            'quantity.integer' => 'Válido apenas números inteiros.',

            'delivery_to.required' => 'Campo entregue para é obrigatório.',

            'return_date.required' => 'Campo data de retorno para é obrigatório.',

            'fk_product_equipament_id.exists:' => 'Produto não encontrado, tente novamente.',
            'fk_user_id.exists' => 'Usuário não encontrado, tente novamente.',

        ];
    }

    ///////////////////////////////////////////////////
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
            'exists' => 'Usuário não encontrado, tente novamente.',
        ];
    }

    ///////////////////////////////////////////////////
    public function rulesReverseFinishedReservation()
    {
        return [
            'reservation_finished' => 'required|boolean|',
            'date_finished' => '',
            'fk_user_id_finished' => 'exists:users,id'
        ];
    }
    public function feedbackReverseFinishedReservation()
    {
        return [
            'required' => 'Campo obrigatório.',
            'boolean' => 'Válido apenas "0" para esse campo.',
            'exists' => 'Usuário não encontrado, tente novamente.',
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
