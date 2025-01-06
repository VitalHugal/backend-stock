<?php

namespace App\Http\Controllers;

use App\Models\Inputs;
use App\Models\SystemLog;
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

                $inputs = Inputs::with(['productEquipament.category' => function ($query) {
                    $query->withTrashed();
                }, 'user' => function ($query) {
                    $query->withTrashed();
                }])
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser)
                            ->withTrashed();
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                $inputs->getCollection()->transform(function ($input) {

                    return [
                        'id' => $input->id ?? null,
                        'quantity' => $input->quantity ?? null,

                        'product_name' => $input->productEquipament && $input->productEquipament->trashed()
                            ? $input->productEquipament->name . ' (Deletado)'
                            : $input->productEquipament->name ?? null,
                        'id_product' => $input->productEquipament
                            ? ($input->productEquipament->trashed()
                                ? $input->productEquipament->id
                                : $input->productEquipament->id)
                            : null,

                        // 'category_name' => $input->productEquipament->category->name ?? null,
                        'category_name' => $input->productEquipament->category->trashed()
                            ? $input->productEquipament->category->name . ' (Deletado)'
                            : $input->productEquipament->category->name ?? null,

                        'fk_user_id' => $input->fk_user_id ?? null,
                        
                        // 'name_user_input' => $input->user->name ?? null,
                        'name_user_input' => $input->user->trashed()
                            ? $input->user->name . ' (Deletado)'
                            : $input->user->name ?? null,

                        'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                        'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Entradas recuperadas com sucesso.',
                    'data' => $inputs,
                ]);
            }

            $inputsAdmin = Inputs::with([
                'productEquipament' => function ($query) {
                    $query->withTrashed();
                },
                'productEquipament.category' => function ($query) {
                    $query->withTrashed();
                },
                'user' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $inputsAdmin->getCollection()->transform(function ($input) {

                return [
                    'id' => $input->id ?? null,
                    'quantity' => $input->quantity ?? null,
                    'product_name' => $input->productEquipament->trashed()
                        ? $input->productEquipament->name . ' (Deletado)'
                        : $input->productEquipament->name ?? null,

                    'id_product' => $input->productEquipament
                        ? ($input->productEquipament->trashed()
                            ? $input->productEquipament->id
                            : $input->productEquipament->id)
                        : null,

                    // 'category_name' => $input->productEquipament->category->name ?? null,
                    'category_name' => $input->productEquipament->category->trashed()
                        ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                        : $input->productEquipament->category->name ?? null,

                    'fk_user_id' => $input->fk_user_id ?? null,
                    
                    // 'name_user_input' => $input->user->name ?? null,
                    'name_user_input' => $input->user->trashed()
                        ? $input->user->name . ' (Deletado)'
                        : $input->user->name ?? null,
                        
                    'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                    'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
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

                $inputs = Inputs::withTrashed()
                    ->with([
                        'productEquipament.category' => function ($query) {
                            $query->withTrashed();
                        },
                        'user' => function ($query) {
                            $query->withTrashed();
                        },
                    ])
                    ->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->withTrashed();
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->get()
                    ->map(function ($input) {

                        return [
                            'id' => $input->id ?? null,
                            'quantity' => $input->quantity ?? null,
                            'product_name' => $input->productEquipament->trashed()
                                ? $input->productEquipament->name . ' (Deletado)'
                                : $input->productEquipament->name ?? null,

                            'id_product' => $input->productEquipament
                                ? ($input->productEquipament->trashed()
                                    ? $input->productEquipament->id
                                    : $input->productEquipament->id)
                                : null,
                                
                            // 'category_name' => $input->productEquipament->category->name ?? null,
                            'category_name' => $input->productEquipament->category->trashed()
                                ? $input->productEquipament->category->name . ' (Deletado)'
                                : $input->productEquipament->category->name ?? null,
                                
                            'fk_user_id' => $input->fk_user_id ?? null,
                            
                            // 'name_user_input' => $input->user->name ?? null,
                            'name_user_input' => $input->user->trashed()
                                ? $input->user->name . ' (Deletado)'
                                : $input->user->name ?? null,
                                
                            'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                            'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,

                        ];
                    });

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada recuperada com sucesso.',
                    'data' => $inputs,
                ]);
            }

            $inputsAdmin = Inputs::withTrashed()
                ->with([
                    'productEquipament.category' => function ($query) {
                        $query->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->withTrashed();
                    },
                ])
                ->where('id', $id)
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })
                ->get()
                ->map(function ($input) {

                    return [
                        'id' => $input->id ?? null,
                        'quantity' => $input->quantity ?? null,
                        'product_name' => $input->productEquipament->trashed()
                            ? $input->productEquipament->name . ' (Deletado)'
                            : $input->productEquipament->name ?? null,

                        'id_product' => $input->productEquipament
                            ? ($input->productEquipament->trashed()
                                ? $input->productEquipament->id
                                : $input->productEquipament->id)
                            : null,
                            
                        // 'category_name' => $input->productEquipament->category->name ?? null,
                        'category_name' => $input->productEquipament->category->trashed()
                            ? $input->productEquipament->category->name . ' (Deletado)'
                            : $input->productEquipament->category->name ?? null,
                            
                        'fk_user_id' => $input->fk_user_id ?? null,
                        
                        // 'name_user_input' => $input->user->name ?? null,
                        'name_user_input' => $input->user->trashed()
                            ? $input->user->name . ' (Deletado)'
                            : $input->user->name ?? null,
                            
                        'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                        'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
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
        DB::beginTransaction();

        try {
            $user = $request->user();
            $idUser = $user->id;

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

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'inputs',
                    'record_id' => $input->id,
                    'description' => 'Adicionou uma entrada.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada criada com sucesso.',
                    'data' => $input,
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $idUser = $user->id;
            $date = now();

            $updateInput = $this->input->find($id);

            if (!$updateInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

            $originalData = $updateInput->getOriginal();

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

            // Verificando as mudanças e criando a string de log
            $changes = $updateInput->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'inputs',
                'record_id' => $id,
                'description' => 'Atualizou uma entrada. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            if ($updateInput) {
                Log::info("User nº:{$idUser} updated entry nº:{$id} on {$date}");

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada atualizada com sucesso.',
                    'data' => $updateInput,
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

            $deleteInput = $this->input->find($id);

            if (!$deleteInput) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteInput->delete();

            if ($deleteInput) {
                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'inputs',
                    'record_id' => $id,
                    'description' => 'Excluiu uma entrada.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada removida com sucesso.',
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