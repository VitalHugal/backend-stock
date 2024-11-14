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
                    ->get()
                    ->map(function ($input) {
                        return [
                            'id' => $input->id,
                            'product_name' => $input->productEquipament->name,
                            'category_name' => $input->productEquipament->category->name,
                            'quantity' => $input->quantity,
                            'username' => $input->user->name,
                            'fk_user_id' => $input->fk_user_id,
                            'created_at' => $input->created_at,
                            'updated_at' => $input->updated_at,

                        ];
                    });

                if ($inputs == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma entrada encontrada.',
                    ]);
                }

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
                ->get()
                ->map(function ($input) {
                    return [
                        'id' => $input->id,
                        'product_name' => $input->productEquipament->name,
                        'category_name' => $input->productEquipament->category->name,
                        'quantity' => $input->quantity,
                        'username' => $input->user->name,
                        'fk_user_id' => $input->fk_user_id,
                        'created_at' => $input->created_at,
                        'updated_at' => $input->updated_at,
                    ];
                });

            if ($inputsAdmin == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

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
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();


            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($user->level == 'user') {

                $inputRequest = Inputs::where('id', $id)->first();

                if ($inputRequest) {
                    $productInExits = $inputRequest->fk_product_equipament_id;
                    $productEspecific = ProductEquipament::where('id', $productInExits)->first();
                    $verifyPresenceProdcutEspecificInCategory = in_array($productEspecific, $categoryUser);

                    if ($verifyPresenceProdcutEspecificInCategory === false) {
                        return response()->json([
                            'sucess' => false,
                            'message' => 'Você não pode ter acesso a um produto que não pertence ao seu setor.'
                        ]);
                    }
                }

                $input = Inputs::with(['productEquipament.category', 'user'])
                    ->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })->first();

                if (!$input) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Entrada não encontrada.',
                    ]);
                }

                $inputDataUser = [
                    'input_id' => $input->id,
                    'product_name' => $input->productEquipament->name,
                    'category_name' => $input->productEquipament->category->name,
                    'quantity' => $input->quantity,
                    'username' => $input->user->name,
                    'fk_user_id' => $input->fk_user_id,
                    'created_at' => $input->created_at,
                    'updated_at' => $input->updated_at,
                ];

                if ($inputDataUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma entrada encontrada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada recuperada com sucesso.',
                    'data' => $inputDataUser,
                ]);
            }

            $input = Inputs::with(['productEquipament.category'])
                ->where('id', $id)
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })->first();

            if (!$input) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entrada não encontrada.',
                ]);
            }

            $inputDataAdim = [
                'input_id' => $input->id,
                'product_name' => $input->productEquipament->name,
                'category_name' => $input->productEquipament->category->name,
                'quantity' => $input->quantity,
                'username' => $input->user->name,
                'fk_user_id' => $input->fk_user_id,
                'created_at' => $input->created_at,
                'updated_at' => $input->updated_at,
            ];

            if ($inputDataAdim == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Entrada recuperada com sucesso.',
                'data' => $inputDataAdim,
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