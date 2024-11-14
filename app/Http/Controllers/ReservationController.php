<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            if (in_array(1, $categoryUser, true) || in_array(5, $categoryUser, true)) {

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
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->get()
                    ->map(function ($reservation) {
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

                if ($reservations === null) {
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
            }

            if ($user->level == 'admin') {

                if ($request->has('reservation_finished')) {
                    $reservationFilter = Reservation::filterReservations($request);

                    return response()->json([
                        'success' => true,
                        'message' => 'Reservas com filtro recuperadas com sucesso.',
                        'data' => $reservationFilter,
                    ]);
                }

                $reservationsAdmin = Reservation::with(['productEquipament.category', 'user', 'userFinished'])
                    ->get()
                    ->map(function ($reservation) {
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

                if ($reservationsAdmin === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma reserva encontrada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Todas as reservas recuperadas com sucesso',
                    'data' => $reservationsAdmin,
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

            if ($user->level == 'user') {
                $product = ProductEquipament::where('id', $id)->first();

                if ($product) {
                    $verifyPresenceProdcutEspecificInCategory = in_array($product->fk_category_id, $categoryUser);

                    if ($verifyPresenceProdcutEspecificInCategory === false) {
                        return response()->json([
                            'sucess' => false,
                            'message' => 'Você não pode ter acesso a um produto que não pertence ao seu setor.'
                        ]);
                    }
                }
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

            (int)$productQuantityMin = $productEquipamentUser->quantity_min;

            if ($productEquipamentUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
            }

            $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $id)->sum('quantity');
            $quantityTotalExits = Exits::where('fk_product_equipament_id', $id)->sum('quantity');
            $quantityReserveNotFinished = Reservation::where('reservation_finished', 0)
                ->where('fk_user_id_finished', null)
                ->sum('quantity');

            $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

            if ($quantityTotalProduct <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto indisponível.',
                ]);
            }

            if ($request->quantity > $quantityTotalProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade solicita indisponível no estoque. Temos apenas ' . $quantityTotalProduct . ' unidade(s).',
                ]);
            }

            if ($request->quantity == '0' || $request->quantity == '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade minima: 1.',
                ]);
            }

            if ($request->fk_product_equipament_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Divergência na identifição do produto/equipamento.',
                ]);
            }

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            if ($validateData) {
                $reservation = $this->reservation->create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'fk_user_id' => $request->fk_user_id,
                    'reason_project' => $request->reason_project,
                    'observation' => $request->observation,
                    'quantity' => $request->quantity,
                    'withdrawal_date' => $request->withdrawal_date,
                    'return_date' => $request->return_date,
                    'delivery_to' => $request->delivery_to,
                    'reservation_finished' => false,
                    'date_finished' => null,
                    'fk_user_id_finished' => null,
                ]);
            }

            if ($reservation) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reserva concluída com sucesso.',
                    'data' => $reservation,
                ]);
            }
            $date = now();

            if (isset($reservation)) {

                // $newQuantityTotal = $quantityTotalProduct - $reservation['quantity'];

                // if ($newQuantityTotal <= $productQuantityMin) {

                //     $updateInputExists = false;
                //     $insertInput = false;

                //     $productAlert = DB::table('product_alerts')
                //         ->where('fk_product_equipament_id', $id)
                //         ->whereNull('deleted_at')
                //         ->first();

                //     if ($productAlert) {

                //         $updateInputExists = DB::table('product_alerts')
                //             ->where('fk_product_equipament_id', $id)
                //             ->update([
                //                 'quantity_min' => $productQuantityMin,
                //                 'fk_category_id' => $productEquipamentUser->fk_category_id,
                //                 'created_at' => $date,
                //             ]) > 0; // Retorna true se pelo menos uma linha foi afetada
                //     } else {
                //         $insertInput = DB::table('product_alerts')
                //             ->insert([
                //                 'fk_product_equipament_id' => $id,
                //                 'quantity_min' => $productQuantityMin,
                //                 'fk_category_id' => $productEquipamentUser->fk_category_id,
                //                 'created_at' => $date,
                //             ]);
                //     }


                //     if ($updateInputExists || $updateInputExists == false || $insertInput) {
                //         return response()->json([
                //             'success' => true,
                //             'message' => 'Retirada concluída com sucesso',
                //             'data' => $reservation,
                //         ]);
                //     }
                // }
                return response()->json([
                    'success' => true,
                    'message' => 'Retirada concluída com sucesso',
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

            $productQuantityMin = $product->quantity_min;

            $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $fk_product)->sum('quantity');
            $quantityTotalExits = Exits::where('fk_product_equipament_id', $fk_product)->sum('quantity');
            $quantityReserveNotFinished = Reservation::where('reservation_finished', 0)
                ->where('fk_user_id_finished', null)
                ->sum('quantity');

            $quantityTotalProduct = ($quantityTotalInputs) - ($quantityTotalExits + $quantityReserveNotFinished);

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            if ($quantityTotalProduct <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto indisponível.',
                ]);
            }

            if ($request->quantity == '0' || $request->quantity == '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade minima: 1.',
                ]);
            }

            if ((int)$quantityOld > (int)$quantityNew) {
                $returnDB = $quantityOld - $quantityNew;
                $updateReservation->update(['quantity' => $updateReservation->quantity + $returnDB]);
                Log::info("User nº:{$idUserRequest} updates quantity from product in reserve nº:{$id}. Returned {$returnDB} unit for bank of data.");
            } elseif ((int)$quantityNew > (int)$quantityOld) {
                $removeDB = $quantityNew - $quantityOld;

                if ($quantityTotalProduct < $removeDB) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalProduct . ' unidades disponíveis.',
                    ]);
                }

                $updateReservation->update(['quantity' => $updateReservation->quantity - $removeDB]);
                Log::info("User nº:{$idUserRequest} updates quantity from product in reserve nº:{$id}. Returned {$removeDB} unit for bank of data.");
            }

            $updateReservation->fill($validateData);
            $updateReservation->save();

            $date = now();

            if ($updateReservation) {
                // $newQuantityTotal = $quantityTotalProduct - $updateReservation['quantity'];

                // if ($newQuantityTotal <= $productQuantityMin) {
                //     $productAlert = DB::table('product_alerts')
                //         ->where('fk_product_equipament_id', $fk_product)
                //         ->whereNull('deleted_at')
                //         ->first();

                //     if ($productAlert) {
                //         DB::table('product_alerts')
                //             ->where('fk_product_equipament_id', $fk_product)
                //             ->update([
                //                 'quantity_min' => $productQuantityMin,
                //                 'fk_category_id' => $product->fk_category_id,
                //                 'created_at' => $date,
                //             ]);
                //     } else {
                //         DB::table('product_alerts')->insert([
                //             'fk_product_equipament_id' => $fk_product,
                //             'quantity_min' => $productQuantityMin,
                //             'fk_category_id' => $product->fk_category_id,
                //             'created_at' => $date,
                //         ]);
                //     }
                // }

                return response()->json([
                    'success' => true,
                    'message' => 'Retirada atualizada com sucesso',
                    'data' => $updateReservation,
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

            $deleteReservation->delete();

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

            if ($reservation->reservation_finished == true && $reservation->date_finished !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva já foi finalizada.',
                ]);
            }

            $reservation->fill(
                $request->validate(
                    $this->reservation->rulesFinishedReservation(),
                    $this->reservation->feedbackFinishedReservation()
                )
            );

            $reservation->fk_user_id_finished = $idUserRequest;
            $reservation->date_finished = now();

            $reservation->save();

            if ($reservation) {

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