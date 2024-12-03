<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use App\Models\SystemLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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

                if ($request->has('reservation_finished') && $request->input('reservation_finished') != '') {

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
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                $reservations->getCollection()->transform(function ($reservation) {

                    $withdrawal_date = 'withdrawal_date';
                    $return_date = 'return_date';
                    $updated_at = 'updated_at';
                    $created_at = 'created_at';

                    if ($reservation->return_date < now() && (int)$reservation->reservation_finished === 0) {
                        $status = 'Delayed';
                        Reservation::where('id', $reservation->id)->update(['status' => $status]);
                    }

                    return [
                        'id' => $reservation->id,
                        'fk_user_id_create' => $reservation->fk_user_id,
                        'name_user_create' => $reservation->user->name ?? null,
                        'reason_project' => $reservation->reason_project,
                        'observation' => $reservation->observation,
                        'quantity' => $reservation->quantity,
                        'withdrawal_date' => $this->reservation->getFormattedDate($reservation, $withdrawal_date),
                        'return_date' => $this->reservation->getFormattedDate($reservation, $return_date),
                        'delivery_to' => $reservation->delivery_to,
                        'status' => $reservation->status,
                        'reservation_finished' => $reservation->reservation_finished,
                        'date_finished' => $reservation->date_finished,
                        'fk_user_id_finished' => $reservation->fk_user_id_finished,
                        'name_user_finished' => $reservation->userFinished->name ?? null,
                        'product_name' => $reservation->productEquipament->name ?? null,
                        'id_product' => $reservation->productEquipament->id ?? null,
                        'category_name' => $reservation->productEquipament->category->name ?? null,
                        'created_at' => $this->reservation->getFormattedDate($reservation, $created_at),
                        'updated_at' => $this->reservation->getFormattedDate($reservation, $updated_at),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Todas as reservas recuperadas com sucesso.',
                    'data' => $reservations,
                ]);
            }

            if ($user->level == 'admin') {

                if ($request->has('reservation_finished') && $request->input('reservation_finished') != '') {

                    $reservationFilter = Reservation::filterReservations($request);

                    return response()->json([
                        'success' => true,
                        'message' => 'Reservas com filtro recuperadas com sucesso.',
                        'data' => $reservationFilter,
                    ]);
                }

                $reservationsAdmin = Reservation::with(['productEquipament.category', 'user', 'userFinished'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                $reservationsAdmin->getCollection()->transform(function ($reservation) {

                    $withdrawal_date_admin = 'withdrawal_date';
                    $return_date_admin = 'return_date';
                    $created_at_admin = 'created_at';
                    $updated_at_admin = 'updated_at';

                    if ($reservation->return_date < now() && (int)$reservation->reservation_finished === 0) {
                        $status = 'Delayed';
                        Reservation::where('id', $reservation->id)->update(['status' => $status]);
                    }

                    return [
                        'id' => $reservation->id,
                        'fk_user_id_create' => $reservation->fk_user_id,
                        'name_user_create' => $reservation->user->name ?? null,
                        'reason_project' => $reservation->reason_project,
                        'observation' => $reservation->observation,
                        'quantity' => $reservation->quantity,
                        'withdrawal_date' => $this->reservation->getFormattedDate($reservation, $withdrawal_date_admin),
                        'return_date' => $this->reservation->getFormattedDate($reservation, $return_date_admin),
                        'delivery_to' => $reservation->delivery_to,
                        'status' => $reservation->status,
                        'reservation_finished' => $reservation->reservation_finished,
                        'date_finished' => $reservation->date_finished,
                        'fk_user_id_finished' => $reservation->fk_user_id_finished,
                        'name_user_finished' => $reservation->userFinished->name ?? null,
                        'product_name' => $reservation->productEquipament->name ?? null,
                        'id_product' => $reservation->productEquipament->id ?? null,
                        'category_name' => $reservation->productEquipament->category->name ?? null,
                        'created_at' => $this->reservation->getFormattedDate($reservation, $created_at_admin),
                        'updated_at' => $this->reservation->getFormattedDate($reservation, $updated_at_admin),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Todas as reservas recuperadas com sucesso.',
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
                $idProduct = Reservation::where('id', $id)->get('fk_product_equipament_id');

                $product = ProductEquipament::where('id', $idProduct)->first();

                if ($product) {
                    $verifyPresenceProdcutEspecificInCategory = in_array($product->fk_category_id, $categoryUser);

                    if ($verifyPresenceProdcutEspecificInCategory === false) {
                        return response()->json([
                            'sucess' => false,
                            'message' => 'Você não pode ter acesso a um produto que não pertence ao seu setor.'
                        ]);
                    }
                }

                $reservation = Reservation::with(['productEquipament.category', 'user'])
                    ->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->first();

                if (!$reservation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reserva não encontrada.',
                    ]);
                }

                $withdrawal_date = 'withdrawal_date';
                $return_date = 'return_date';
                $updated_at = 'updated_at';
                $created_at = 'created_at';

                if ($reservation->return_date < now() && (int)$reservation->reservation_finished === 0) {
                    $status = 'Delayed';
                    Reservation::where('id', $reservation->id)->update(['status' => $status]);
                }

                $reservationData = [
                    'id' => $reservation->id,
                    'fk_user_id_create' => $reservation->fk_user_id,
                    'name_user_create' => $reservation->user->name ?? null,
                    'reason_project' => $reservation->reason_project,
                    'observation' => $reservation->observation,
                    'quantity' => $reservation->quantity,
                    'withdrawal_date' => $this->reservation->getFormattedDate($reservation, $withdrawal_date),
                    'return_date' => $this->reservation->getFormattedDate($reservation, $return_date),
                    'delivery_to' => $reservation->delivery_to,
                    'status' => $reservation->status,
                    'reservation_finished' => $reservation->reservation_finished,
                    'date_finished' => $reservation->date_finished,
                    'fk_user_id_finished' => $reservation->fk_user_id_finished,
                    'name_user_finished' => $reservation->userFinished->name ?? null,
                    'product_name' => $reservation->productEquipament->name ?? null,
                    'id_product' => $reservation->productEquipament->id ?? null,
                    'category_name' => $reservation->productEquipament->category->name ?? null,
                    'created_at' => $this->reservation->getFormattedDate($reservation, $created_at),
                    'updated_at' => $this->reservation->getFormattedDate($reservation, $updated_at),
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

            $withdrawal_date_admin = 'withdrawal_date';
            $return_date_admin = 'return_date';
            $created_at_admin = 'created_at';
            $updated_at_admin = 'updated_at';

            if ($reservation->return_date < now() && (int)$reservation->reservation_finished === 0) {
                $status = 'Delayed';
                Reservation::where('id', $reservation->id)->update(['status' => $status]);
            }

            $reservationDataAdmin = [
                'id' => $reservation->id,
                'fk_user_id_create' => $reservation->fk_user_id,
                'name_user_create' => $reservation->user->name ?? null,
                'reason_project' => $reservation->reason_project,
                'observation' => $reservation->observation,
                'quantity' => $reservation->quantity,
                'withdrawal_date' => $this->reservation->getFormattedDate($reservation, $withdrawal_date_admin),
                'return_date' => $this->reservation->getFormattedDate($reservation, $return_date_admin),
                'delivery_to' => $reservation->delivery_to,
                'status' => $reservation->status,
                'reservation_finished' => $reservation->reservation_finished,
                'date_finished' => $reservation->date_finished,
                'fk_user_id_finished' => $reservation->fk_user_id_finished,
                'name_user_finished' => $reservation->userFinished->name ?? null,
                'product_name' => $reservation->productEquipament->name ?? null,
                'id_product' => $reservation->productEquipament->id ?? null,
                'category_name' => $reservation->productEquipament->category->name ?? null,
                'created_at' => $this->reservation->getFormattedDate($reservation, $created_at_admin),
                'updated_at' => $this->reservation->getFormattedDate($reservation, $updated_at_admin),
            ];

            if ($reservationDataAdmin == null) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhuma reserva encontrada.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva recuperada com sucesso.',
                'data' => $reservationDataAdmin,
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

    public function reservation(Request $request)
    {
        DB::beginTransaction();
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
                    ->whereIn('fk_category_id', $categoryUser)->where('id', $request->fk_product_equipament_id)->first();
            }

            $productEquipamentUser = ProductEquipament::where('id', $request->fk_product_equipament_id)->first();

            (int)$productQuantityMin = $productEquipamentUser->quantity_min;

            if ($productEquipamentUser === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
            }

            $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $request->fk_product_equipament_id)->sum('quantity');
            $quantityTotalExits = Exits::where('fk_product_equipament_id', $request->fk_product_equipament_id)->sum('quantity');
            $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $request->fk_product_equipament_id)
                ->where('reservation_finished', false)
                ->whereNull('date_finished')
                ->whereNull('fk_user_id_finished')
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

            if ($request->quantity == '0' || $request->quantity == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade minima: 1.',
                ]);
            }

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            if ($validateData) {
                $reservation = $this->reservation->create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'fk_user_id' => $idUserRequest,
                    'reason_project' => $request->reason_project,
                    'observation' => $request->observation,
                    'quantity' => $request->quantity,
                    'withdrawal_date' => now(),
                    'return_date' => $request->return_date,
                    'delivery_to' => $request->delivery_to,
                    'status' => 'In progress',
                    'reservation_finished' => false,
                    'date_finished' => null,
                    'fk_user_id_finished' => null,
                ]);
            }

            if ($reservation) {

                SystemLog::create([
                    'fk_user_id' => $idUserRequest,
                    'action' => 'Adicionou',
                    'table_name' => 'reservations',
                    'record_id' => $reservation->id,
                    'description' => 'Adicionou uma reserva.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Reserva concluída com sucesso.',
                    'data' => $reservation,
                ]);
            }
        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }

    public function updateReservation(Request $request, $id)
    {
        DB::beginTransaction();
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

            $originalData = $updateReservation->getOriginal();

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
            $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id',  $fk_product)
                ->where('reservation_finished', false)
                ->whereNull('date_finished')
                ->whereNull('fk_user_id_finished')
                ->sum('quantity');

            $quantityTotalProduct = ($quantityTotalInputs) - ($quantityTotalExits + $quantityReserveNotFinished);

            $validateData = $request->validate(
                $this->reservation->rulesReservation(),
                $this->reservation->feedbackReservation()
            );

            if ($quantityTotalProduct <= 0 && $quantityNew > $quantityOld) {
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

            // Verificando as mudanças e criando a string de log
            $changes = $updateReservation->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUserRequest,
                'action' => 'Atualizou',
                'table_name' => 'reservations',
                'record_id' => $id,
                'description' => 'Atualizou uma reserva. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();


            if ($updateReservation) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retirada atualizada com sucesso',
                    'data' => $updateReservation,
                ]);
            }
        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }
    public function delayedReservations(Request $request)
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

            $reserveAll = Reservation::all();

            foreach ($reserveAll as $reservation) {
                if ($reservation->return_date < now() && (int)$reservation->reservation_finished === 0) {
                    $status = 'Delayed';

                    $reservation->update(['status' => $status]);
                }
            }

            $reservations = Reservation::with('productEquipament', 'category')
                ->where('return_date', '<', Carbon::now())
                ->where('reservation_finished', false)
                ->whereNull('date_finished')
                ->whereNull('fk_user_id_finished')
                ->paginate(10);


            $reservations->getCollection()->transform(function ($reserve) {

                $return_date = "return_date";

                return [
                    'id' => $reserve->id,
                    'delivery_to' => $reserve->delivery_to,
                    'return_date' => $this->reservation->getFormattedDate($reserve, $return_date),
                    'category' => $reserve->productEquipament->category->name,
                    'product_name' => $reserve->productEquipament->name,
                    'quantity' => $reserve->quantity,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Reservas em atraso recuperadas com sucesso.',
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

    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

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

            if ($deleteReservation) {
                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'reservations',
                    'record_id' => $id,
                    'description' => 'Excluiu uma reserva.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reserva removida com sucesso.',
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

    public function pendingReservationCompleted(Request $request, $id)
    {
        DB::beginTransaction();
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

            if ($request->reservation_finished != '1' || $request->reservation_finished != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possivel finalizar, valido apenas 1 para conseguir finalizar.',
                ]);
            }

            $reservation->fk_user_id_finished = $idUserRequest;
            $reservation->date_finished = now();
            $reservation->status = 'Finished';

            $reservation->save();

            SystemLog::create([
                'fk_user_id' => $idUserRequest,
                'action' => 'Finalizou',
                'table_name' => 'reservations',
                'record_id' => $id,
                'description' => 'Finalizou uma reserva.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            if ($reservation) {

                return response()->json([
                    'success' => true,
                    'message' => 'Reserva finalizada com sucesso.',
                ]);
            }
        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error DB: " . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage(),
            ]);
        }
    }
}