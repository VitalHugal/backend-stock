<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class ReservationController extends CrudController
{
    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        parent::__construct($reservation);

        $this->reservation = $reservation;
    }
    public function getAllReservation(Request $request)
    {
        try {
            $user = $request->user();
            $idUserRequest = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUserRequest)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && !in_array(1, $categoryUser) && !in_array(5, $categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }


            if ($request->has('reservation_finished')) {
                $reservationFilter = Reservation::filterReservations($request);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservas com filtro recuperadas com sucesso.',
                    'data' => $reservationFilter,
                ]);
            }

            $reservations = Reservation::with(['productEquipament.category', 'user', 'userFinished'])
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                })
                ->get()
                ->map(function ($reservation) {
                    return [
                        'exit_id' => $reservation->id,
                        'fk_user_id' => $reservation->fk_user_id,
                        'name_user_id' => $reservation->user->name ?? null,
                        'reason_project' => $reservation->reason_project,
                        'observation' => $reservation->observation,
                        'quantity' => $reservation->quantity,
                        'withdrawal_date' => $reservation->withdrawal_date,
                        'return_date' => $reservation->return_date,
                        'delivery_to' => $reservation->delivery_to,
                        'reservation_finished' => $reservation->reservation_finished,
                        'date_finished' => $reservation->date_finished,
                        'fk_user_id_finished' => $reservation->fk_user_id_finished,
                        'name_user_id_finished' => $reservation->userFinished->name ?? null,
                        'product_name' => $reservation->productEquipament->name ?? null,
                        'category_name' => $reservation->productEquipament->category->name ?? null,
                    ];
                });

            if ($reservations == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma reserva encontrada.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todas as reservas recuperadas com sucesso.',
                'data' => $reservations,
            ]);
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function getIdReservation(Request $request, $id)
    {
        try {
            $user = $request->user();
            $idUserRequest = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUserRequest)
                ->pluck('fk_category_id')
                ->toArray();


            if ($user->level !== 'admin' && !in_array(1, $categoryUser) && !in_array(5, $categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $reservation = Reservation::with(['productEquipament.category', 'user'])
                ->where('id', $id) 
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })
                ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva não encontrada.',
                ]);
            }

            $reservationData = [
                'exit_id' => $reservation->id,
                'fk_user_id' => $reservation->fk_user_id,
                'name_user_id' => $reservation->user->name ?? null,
                'reason_project' => $reservation->reason_project,
                'observation' => $reservation->observation,
                'quantity' => $reservation->quantity,
                'withdrawal_date' => $reservation->withdrawal_date,
                'return_date' => $reservation->return_date,
                'delivery_to' => $reservation->delivery_to,
                'reservation_finished' => $reservation->reservation_finished,
                'date_finished' => $reservation->date_finished,
                'fk_user_id_finished' => $reservation->fk_user_id_finished,
                'name_user_id_finished' => $reservation->user->name ?? null,
                'product_name' => $reservation->productEquipament->name ?? null,
                'category_name' => $reservation->productEquipament->category->name ?? null,
            ];

            if ($reservationData == null) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhuma reserva encontrada.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva recuperada com sucesso.',
                'data' => $reservationData,
            ]);
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function reservation(Request $request, $id)
    {
        try {
            $user = $request->user();
            $idUserRequest = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUserRequest)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && !in_array(1, $categoryUser) && !in_array(5, $categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($categoryUser) {
                $productEquipamentUser = ProductEquipament::with('category')
                    ->whereIn('fk_category_id', $categoryUser)->where('id', $id)->first();
            }

            $productEquipamentUser = ProductEquipament::where('id', $id)->first();

            if ($productEquipamentUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
            }

            $quantityProductEquipament = $productEquipamentUser->quantity;

            $numQuantity = intval($request->quantity);

            if ($numQuantity > $quantityProductEquipament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityProductEquipament . ' unidades disponíveis.',
                ]);
            }

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            //dd($request->all());

            $newQuantityProductEquipament = $quantityProductEquipament - $numQuantity;

            if ($request->fk_product_equipament_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Divergência na identifição do produto/equipamento.',
                ]);
            }

            $reservation = $this->reservation->create([
                'fk_product_equipament_id' => $request->fk_product_equipament_id,
                'fk_user_id' => $request->fk_user_id,
                'reason_project' => $request->reason_project,
                'observation' => $request->observation,
                'quantity' => $request->quantity,
                'withdrawal_date' => $request->withdrawal_date,
                'return_date' => $request->return_date,
                'delivery_to' => $request->delivery_to,
                'reservation_finished' => 'false',
                'date_finished' => 'false',
                'fk_user_id_finished' => null,
            ]);

            if ($reservation) {
                ProductEquipament::where('id', $id)->update(['quantity' => $newQuantityProductEquipament]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reserva concluída com sucesso.',
                    'data' => $reservation,
                ]);
            }
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function updateReservation(Request $request, $id)
    {
        try {
            $user = $request->user();
            $idUserRequest = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUserRequest)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && !in_array(1, $categoryUser) && !in_array(5, $categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $updateReservation = $this->reservation->find($id);

            if (!$updateReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma reserva encontrada.',
                ]);
            }

            $fk_product = $updateReservation->fk_product_equipament_id;
            $quantityOld = $updateReservation->quantity;
            $quantityNew = $request->quantity;

            $product = ProductEquipament::find($fk_product);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado.',
                ]);
            }

            $quantityTotalDB = $product->quantity;

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            if ((int)$quantityOld > (int)$quantityNew) {

                $returnDB = $quantityTotalDB + ($quantityOld - $quantityNew);
                $product->update(['quantity' => $returnDB]);
            } elseif ((int)$quantityNew > (int)$quantityOld) {

                $removeDB = $quantityNew - $quantityOld;

                if ($quantityTotalDB < $removeDB) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalDB . ' unidades disponíveis.',
                    ]);
                }
                $product->update(['quantity' => $quantityTotalDB - $removeDB]);
            }

            $updateReservation->fill($validateData);
            $updateReservation->save();

            return response()->json([
                'success' => true,
                'message' => 'Reserva atualizada com sucesso.',
                'data' => $updateReservation,
            ]);
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();
            $level = $user->level;

            if ($level == 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteReservation = $this->reservation->find($id);

            if (!$deleteReservation) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $quantityReturnStock = $deleteReservation->quantity;
            $idProduct = $deleteReservation->fk_product_equipament_id;

            $deleteReservation->delete();

            $product = ProductEquipament::where('id', $idProduct)->first();

            if ($product) {

                $quantityTotalDB = $product->quantity;
                $product->update(['quantity' => $quantityTotalDB + $quantityReturnStock]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva removida com sucesso.',
            ]);
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function pendingReservationCompleted(Request $request, $id)
    {
        try {
            $user = $request->user();
            $idUserRequest = $user->id;

            // dd($idUserRequest);

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUserRequest)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && !in_array(1, $categoryUser) && !in_array(5, $categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $reservation = $this->reservation->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum reserva encontrada com o id informado.",
                ]);
            }

            if ($reservation->reservation_finished == 'true' && $reservation->date_finished !== '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva já foi finalizada.',
                ]);
            }

            $quantityReturnDB = $reservation->quantity;
            $idProduct = $reservation->fk_product_equipament_id;

            $reservation->fill(
                $request->validate(
                    $this->reservation->rulesFinishedReservation(),
                    $this->reservation->feedbackFinishedReservation()
                )
            );

            $reservation->fk_user_id_finished = $idUserRequest;

            $reservation->save();

            if ($reservation) {
                $product = ProductEquipament::where('id', $idProduct)->first();

                if ($product) {
                    $quantityTotalDB = $product->quantity;
                    $product->update(['quantity' => $quantityTotalDB + $quantityReturnDB]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Reserva finalizada com sucesso.',
                ]);
            }
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }
}