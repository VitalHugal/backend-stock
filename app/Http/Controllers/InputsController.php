<?php

namespace App\Http\Controllers;

use App\Models\Inputs;
use App\Http\Controllers\Controller;
use App\Models\ProductEquipament;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputsController extends CrudController
{
    protected $input;

    public function __construct(Inputs $inputs)
    {
        parent::__construct($inputs);

        $this->input = $inputs;
    }

    public function getAllInputs(Request $request)
    {
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            // Verifica o nível de acesso e filtra as saídas
            if ($level == 'user') {
                $inputs = Inputs::with(['productEquipament.category', 'user'])
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                $inputs->getCollection()->transform(function ($input) {

                    $formatedDateWithdrawalDate = explode(" ", $input->created_at);
                    $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                    $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                    $dateFinalCreatedAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                    $formatedDateWithdrawalDate = explode(" ", $input->updated_at);
                    $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                    $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                    $dateFinalUpdateAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                    return [
                        'id' => $input->id,
                        'quantity' => $input->quantity,
                        'id_product' => $input->productEquipament->id,
                        'product_name' => $input->productEquipament->name,
                        'category_name' => $input->productEquipament->category->name,
                        'fk_user_id' => $input->fk_user_id,
                        'name_user_exits' => $input->user->name,
                        'created_at' => $dateFinalCreatedAtDate,
                        'updated_at' => $dateFinalUpdateAtDate,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Entradas recuperadas com sucesso.',
                    'data' => $inputs,
                ]);
            }

            $inputsAdmin = Inputs::with(['productEquipament.category', 'user'])
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $inputsAdmin->getCollection()->transform(function ($input) {

                $formatedDateWithdrawalDate = explode(" ", $input->created_at);
                $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                $dateFinalCreatedAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                $formatedDateWithdrawalDate = explode(" ", $input->updated_at);
                $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                $dateFinalUpdateAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
                
                return [
                    'id' => $input->id,
                    'id_product' => $input->productEquipament->id,
                    'quantity' => $input->quantity,
                    'product_name' => $input->productEquipament->name,
                    'category_name' => $input->productEquipament->category->name,
                    'fk_user_id' => $input->fk_user_id,
                    'name_user_exits' => $input->user->name,
                    'created_at' => $dateFinalCreatedAtDate,
                    'updated_at' => $dateFinalUpdateAtDate,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Entradas recuperadas com sucesso.',
                'data' => $inputsAdmin,
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

    public function getIdInputs(Request $request, $id)
    {
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $verifyId = $this->input->find($id);

            if (!$verifyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }
            // Verifica o nível de acesso e filtra as saídas
            if ($level == 'user') {
                $inputs = Inputs::with(['productEquipament.category', 'user'])->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->get()
                    ->map(function ($input) {

                        $formatedDateWithdrawalDate = explode(" ", $input->created_at);
                        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                        $dateFinalCreatedAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                        $formatedDateWithdrawalDate = explode(" ", $input->updated_at);
                        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                        $dateFinalUpdateAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                        return [
                            'id' => $input->id,
                            'quantity' => $input->quantity,
                            'product_name' => $input->productEquipament->name,
                            'category_name' => $input->productEquipament->category->name,
                            'fk_user_id' => $input->fk_user_id,
                            'name_user_exits' => $input->user->name,
                            'created_at' => $dateFinalCreatedAtDate,
                            'updated_at' => $dateFinalUpdateAtDate,

                        ];
                    });

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada recuperada com sucesso.',
                    'data' => $inputs,
                ]);
            }

            $inputsAdmin = Inputs::with(['productEquipament.category', 'user'])->where('id', $id)
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })
                ->get()
                ->map(function ($input) {

                    $formatedDateWithdrawalDate = explode(" ", $input->created_at);
                    $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                    $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                    $dateFinalCreatedAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                    $formatedDateWithdrawalDate = explode(" ", $input->updated_at);
                    $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
                    $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
                    $dateFinalUpdateAtDate = $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;

                    return [
                        'id' => $input->id,
                        'quantity' => $input->quantity,
                        'product_name' => $input->productEquipament->name,
                        'category_name' => $input->productEquipament->category->name,
                        'fk_user_id' => $input->fk_user_id,
                        'name_user_exits' => $input->user->name,
                        'created_at' => $dateFinalCreatedAtDate,
                        'updated_at' => $dateFinalUpdateAtDate,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Entrada recuperada com sucesso.',
                'data' => $inputsAdmin,
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

    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validatedData = $request->validate(
                $this->input->rulesInputs(),
                $this->input->feedbackInputs()
            );

            if ($validatedData) {
                $input = $this->input->create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'quantity' => $request->quantity,
                    'fk_user_id' => $user->id,
                ]);
            }

            if ($input) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada criada com sucesso.',
                    'data' => $input,
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

    public function update(Request $request, $id)
    {
        try {

            $updateInput = $this->input->find($id);
            $user = $request->user();
            $idUser = $user->id;

            $date = now();

            if (!$updateInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

            $validatedData = [
                'fk_product_equipament_id' => $request->fk_product_equipament_id,
                'quantity' => $request->quantity,
            ];

            $validatedData = $request->validate(
                $this->input->rulesInputs(),
                $this->input->feedbackInputs()
            );

            $updateInput->fill($validatedData);
            $updateInput->save();

            if ($updateInput) {
                Log::info("User nº:{$idUser} updated entry nº:{$id} on {$date}");

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada atualizada com sucesso.',
                    'data' => $updateInput,
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

            $deleteInput = $this->input->find($id);

            if (!$deleteInput) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteInput->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entrada removida com sucesso.',
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
}