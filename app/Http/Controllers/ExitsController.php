<?php

namespace App\Http\Controllers;

use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use App\Models\SystemLog;
use App\Services\InputService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input;

class ExitsController extends CrudController
{
    protected $exits;
    protected $input_service;

    public function __construct(Exits $exits, InputService $inputService)
    {
        parent::__construct($exits, $inputService);

        $this->exits = $exits;
        $this->input_service = $inputService;
    }

    // public function __construct(Exits $exits, Inputs $inputs)
    // {
    //     parent::__construct($exits, $inputs);

    //     $this->exits = $exits;
    //     $this->inputs = $inputs;
    // }

    public function getAllExits(Request $request)
    {
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && $user->level !== 'manager' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($level == 'user') {
                $exits = Exits::withTrashed()
                    ->with(['productEquipament.category' => function ($query) {
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

                // Transformando os itens dentro da paginação
                $exits->getCollection()->transform(function ($exit) {

                    return [
                        'id' => $exit->id ?? null,
                        'fk_user_id' => $exit->fk_user_id ?? null,

                        // 'name_user_exits' => $exit->user->name ?? null,
                        'name_user_exits' => $exit->user->trashed()
                            ? $exit->user->name . ' (Deletado)'
                            : $exit->user->name ?? null,

                        'reason_project' => $exit->reason_project ?? null,
                        'observation' => $exit->observation ?? null,
                        'quantity' => $exit->quantity ?? null,
                        'delivery_to' => $exit->delivery_to ?? null,
                        'discarded' => $exit->discarded ?? null,

                        // 'product_name' => $exit->productEquipament->name ?? null,
                        // 'id_product' => $exit->productEquipament->id ?? null,
                        'product_name' => $exit->productEquipament && $exit->productEquipament->trashed()
                            ? $exit->productEquipament->name . ' (Deletado)'
                            : $exit->productEquipament->name ?? null,
                        'id_product' => $exit->productEquipament
                            ? ($exit->productEquipament->trashed()
                                ? $exit->productEquipament->id
                                : $exit->productEquipament->id)
                            : null,

                        // 'category_name' => $exit->productEquipament->category->name ?? null,
                        'category_name' => $exit->productEquipament->category->trashed()
                            ? $exit->productEquipament->category->name . ' (Deletado)'
                            : $exit->productEquipament->category->name ?? null,

                        'created_at' => $this->exits->getFormattedDate($exit, 'created_at') ?? null,
                        'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at') ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Saídas recuperadas com sucesso.',
                    'data' => $exits,
                ]);
            }

            //ADMIN OR MANAGER

            $exitsAdmin = Exits::with([
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


            // Transformando os itens dentro da paginação
            $exitsAdmin->getCollection()->transform(function ($exit) {

                return [
                    'id' => $exit->id ?? null,
                    'fk_user_id' => $exit->fk_user_id ?? null,

                    // 'name_user_exits' => $exit->user->name ?? null,
                    'name_user_exits' => $exit->user->trashed()
                        ? $exit->user->name . ' (Deletado)'
                        : $exit->user->name ?? null,

                    'reason_project' => $exit->reason_project ?? null,
                    'observation' => $exit->observation ?? null,
                    'quantity' => $exit->quantity ?? null,
                    'delivery_to' => $exit->delivery_to ?? null,
                    'discarded' => $exit->discarded ?? null,

                    // 'product_name' => $exit->productEquipament->name ?? null,
                    // 'id_product' => $exit->productEquipament->id ?? null,
                    'product_name' => $exit->productEquipament->trashed()
                        ? $exit->productEquipament->name . ' (Deletado)'
                        : $exit->productEquipament->name ?? null,
                    'id_product' => $exit->productEquipament
                        ? ($exit->productEquipament->trashed()
                            ? $exit->productEquipament->id
                            : $exit->productEquipament->id)
                        : null,

                    // 'category_name' => $exit->productEquipament->category->name ?? null,
                    'category_name' => $exit->productEquipament->category->trashed()
                        ? $exit->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                        : $exit->productEquipament->category->name ?? null,

                    'created_at' => $this->exits->getFormattedDate($exit, 'created_at') ?? null,
                    'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at') ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Saídas recuperadas com sucesso.',
                'data' => $exitsAdmin,
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

    public function getIdExits(Request $request, $id)
    {
        try {
            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();


            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($user->level == 'user') {

                $exitRequest = Exits::where('id', $id)->first();

                if ($exitRequest) {
                    $productInExits = $exitRequest->fk_product_equipament_id;
                    $productEspecific = ProductEquipament::where('id', $productInExits)->first();
                    $verifyPresenceProdcutEspecificInCategory = in_array($productEspecific->id, $categoryUser);

                    if ($verifyPresenceProdcutEspecificInCategory === false) {
                        return response()->json([
                            'sucess' => false,
                            'message' => 'Você não pode ter acesso a um produto que não pertence ao seu setor.'
                        ]);
                    }
                }

                $exit = Exits::withTrashed()
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
                    ->first();


                if (!$exit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saída não encontrada.',
                    ]);
                }

                $exitDataUser = [
                    'id' => $exit->id ?? null,
                    'fk_user_id' => $exit->fk_user_id ?? null,

                    // 'name_user_exits' => $exit->user->name ?? null,
                    'name_user_exits' => $exit->user->trashed()
                        ? $exit->user->name . ' (Deletado)'
                        : $exit->user->name ?? null,

                    'reason_project' => $exit->reason_project ?? null,
                    'observation' => $exit->observation ?? null,
                    'quantity' => $exit->quantity ?? null,
                    'delivery_to' => $exit->delivery_to ?? null,
                    'discarded' => $exit->discarded ?? null,

                    'product_name' => $exit->productEquipament && $exit->productEquipament->trashed()
                        ? $exit->productEquipament->name . ' (Deletado)'
                        : $exit->productEquipament->name ?? null,

                    'id_product' => $exit->productEquipament
                        ? ($exit->productEquipament->trashed()
                            ? $exit->productEquipament->id
                            : $exit->productEquipament->id)
                        : null,

                    // 'category_name' => $exit->productEquipament->category->name ?? null,
                    'category_name' => $exit->productEquipament->category->trashed()
                        ? $exit->productEquipament->category->name . ' (Deletado)'
                        : $exit->productEquipament->category->name ?? null,

                    'created_at' => $this->exits->getFormattedDate($exit, 'created_at') ?? null,
                    'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at') ?? null,
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Saída recuperada com sucesso.',
                    'data' => $exitDataUser,
                ]);
            }

            //ADMIN OR MANAGER

            $exit = Exits::withTrashed()
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
                ->first();


            if (!$exit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saída não encontrada.',
                ]);
            }

            $exitDataAdmin = [
                'id' => $exit->id ?? null,
                'fk_user_id' => $exit->fk_user_id ?? null,

                // 'name_user_exits' => $exit->user->name ?? null,
                'name_user_exits' => $exit->user->trashed()
                    ? $exit->user->name . ' (Deletado)'
                    : $exit->user->name ?? null,

                'reason_project' => $exit->reason_project ?? null,
                'observation' => $exit->observation ?? null,
                'quantity' => $exit->quantity ?? null,
                'delivery_to' => $exit->delivery_to ?? null,
                'discarded' => $exit->discarded ?? null,

                'product_name' => $exit->productEquipament && $exit->productEquipament->trashed()
                    ? $exit->productEquipament->name . ' (Deletado)'
                    : $exit->productEquipament->name ?? null,

                'id_product' => $exit->productEquipament
                    ? ($exit->productEquipament->trashed()
                        ? $exit->productEquipament->id
                        : $exit->productEquipament->id)
                    : null,

                // 'category_name' => $exit->productEquipament->category->name ?? null,
                'category_name' => $exit->productEquipament->category->trashed()
                    ? $exit->productEquipament->category->name . ' (Deletado)'
                    : $exit->productEquipament->category->name ?? null,

                'created_at' => $this->exits->getFormattedDate($exit, 'created_at') ?? null,
                'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at') ?? null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Saída recuperada com sucesso.',
                'data' => $exitDataAdmin,
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

    public function exits(Request $request)
    {
        DB::beginTransaction();
        try {

            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();
                
                if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você não tem permissão de acesso para seguir adiante.',
                    ]);
                }

            $productEquipamentUser = ProductEquipament::where('id', $request->fk_product_equipament_id)->where('is_group', 0)->first();

            if ($productEquipamentUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado.',
                ]);
            }
            
            (int)$productQuantityMin = $productEquipamentUser->quantity_min;

            if ($user->level == 'user') {
                $validationExits = in_array($productEquipamentUser->fk_category_id, $categoryUser);
                if ($validationExits === false) {
                    return response()->json([
                        'sucess' => false,
                        'message' => 'Você não pode realizar saída(s) de produto(s) que não pertence ao seu nivel de acesso.'
                    ]);
                }
            }

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

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
                    'message' => 'Produto esgotado.',
                ]);
            }

            if ($request->quantity > $quantityTotalProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade solicitada indisponível no estoque. Temos apenas ' . $quantityTotalProduct . ' unidade(s).',
                ]);
            }

            if ($request->quantity == '0' || $request->quantity == '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade minima: 1.',
                ]);
            }

            if ($productEquipamentUser->expiration_date == 1 && $request->discarded == 0) {

                $fk_product_equipament_id = $request->fk_product_equipament_id;
                $inputIdOrderExpirationDateFirst = $this->input_service->getInputsWithOrderByExpirationDate($request, $fk_product_equipament_id);

                if (!$request->fk_inputs_id == $inputIdOrderExpirationDateFirst || $request->fk_inputs_id == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A entrada relacionada não corresponde à definida pela plataforma. Por favor, verifique.'
                    ]);
                }

                $data = $inputIdOrderExpirationDateFirst->original['data'];

                if ($data['storage_locations_id'] == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não é possivel realizar essa saída, entrada não informa local de armazenamento.',
                    ]);
                }

                if ($data['status'] == 'Finalizado') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não é possivel realizar saída, entrada finalizada ou vencida.',
                    ]);
                }

                if ($request->quantity > $data['quantity_active']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Limite-se a quantidade disponível nessa entrada.'. $data['quantity_active'],
                    ]);
                }

                $input = Inputs::where('id', $request->fk_inputs_id)->first();

                if ($validateData) {
                    $exits = Exits::create([
                        'fk_product_equipament_id' => $request->fk_product_equipament_id,
                        'fk_user_id' => $idUser,
                        'reason_project' => $request->reason_project,
                        'observation' => $request->observation,
                        'quantity' => $request->quantity,
                        'delivery_to' => $request->delivery_to,
                        'fk_inputs_id' => $request->fk_inputs_id,
                        'discarded' => $request->discarded,
                    ]);

                    $input->quantity_active -= $request->quantity;
                    $input->save();
                }

                if ($input->quantity_active == 0 && $exits['discarded'] == 0) {
                    $status = 'Finalizado';
                    $input->status = $status;
                    $input->save();
                }
            }

            $input = Inputs::where('id', $request->fk_inputs_id)->first();

            if ($validateData) {
                $exits = Exits::create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'fk_user_id' => $idUser,
                    'reason_project' => $request->reason_project,
                    'observation' => $request->observation,
                    'quantity' => $request->quantity,
                    'delivery_to' => $request->delivery_to,
                    'fk_inputs_id' => null,
                    'discarded' => $request->discarded,
                ]);
            }

            if ($exits) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'exits',
                    'record_id' => $exits->id,
                    'description' => 'Adicionou uma saída.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Saída concluída com sucesso',
                    'data' => $exits,
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

    public function updateExits(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $updateExits = $this->exits->find($id);

            if (!$updateExits) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma saída encontrada.',
                ]);
            }

            $originalData = $updateExits->getOriginal();

            $fk_product = $updateExits->fk_product_equipament_id;
            $quantityOld = $updateExits->quantity;
            $quantityNew = $request->quantity;
            $fk_inputs_id = $updateExits->fk_inputs_id;

            $input = Inputs::where('id', $fk_inputs_id)->first();

            if (!$input) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum entrada encontrado.',
                ]);
            }

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
            $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $fk_product)
                ->where('reservation_finished', false)
                ->whereNull('date_finished')
                ->whereNull('fk_user_id_finished')
                ->sum('quantity');

            $quantityTotalProduct = ($quantityTotalInputs) - ($quantityTotalExits + $quantityReserveNotFinished);

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

            if ($fk_inputs_id != $request->fk_inputs_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é permitido alterar o id da entrada.',
                ]);
            }

            if ($quantityNew > $quantityOld) {
                $result = ($quantityNew - $quantityOld);

                if ($result > $input->quantity_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade disponível nesse relacionamento: ' . $input->quantity_active . ' unidade(s).',
                    ]);
                }
            }

            if ($quantityTotalProduct <= 0 && $quantityNew > $quantityOld) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto indisponível.',
                ]);
            }

            if ($request->quantity == 0 || $request->quantity == '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade minima: 1.',
                ]);
            }

            if ((int)$quantityOld > (int)$quantityNew) {
                $returnDB = $quantityOld - $quantityNew;
                $updateExits->update(['quantity' => $updateExits->quantity + $returnDB]);
                $input->update(['quantity_active' => $input->quantity_active + $returnDB]);

                if ($input->quantity_active > 0) {
                    $this->input_service->updateStatusInput($input);
                }
            } elseif ((int)$quantityNew > (int)$quantityOld) {
                $removeDB = $quantityNew - $quantityOld;

                // if ($quantityTotalProduct < $removeDB) {
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalProduct . ' unidades disponíveis.',
                //     ]);
                // }

                if ($input->quantity_active < $removeDB) {
                    return response()->json([
                        'success' => false,
                        // 'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalProduct . ' unidades disponíveis.',
                        'message' => 'Limite-se a quantidade disponível nessa entrada.',
                    ]);
                }

                // dd('aqui');

                $updateExits->update(['quantity' => $updateExits->quantity - $removeDB]);
                $input->update(['quantity_active' => $input->quantity_active - $removeDB]);

                if ($input->quantity_active == 0) {
                    $status = 'Finalizado';
                    $input->status = $status;
                    $input->save();
                }
                if ($input->quantity_active > 0) {
                    $this->input_service->updateStatusInput($input);
                }
            }

            $updateExits->fill($validateData);
            $updateExits->save();


            // Verificando as mudanças e criando a string de log
            $changes = $updateExits->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} #";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'exits',
                'record_id' => $id,
                'description' => 'Atualizou uma saída. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            if ($updateExits) {
                return response()->json([
                    'success' => true,
                    'message' => 'Saída atualizada com sucesso',
                    'data' => $updateExits,
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
            $idUser = $user->id;
            $level = $user->level;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteExits = $this->exits->find($id);

            if (!$deleteExits) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $quantityReturnDB = $deleteExits->quantity;

            if ($deleteExits->fk_inputs_id) {
                $fk_inputs_id_returned_quantity = $deleteExits->fk_inputs_id;
            }
            $deleteExits->delete();

            if ($deleteExits) {

                if ($fk_inputs_id_returned_quantity) {
                    $result = Inputs::where('id', $fk_inputs_id_returned_quantity)->first();

                    if ($result) {
                        $quantity_active = $result->quantity_active;
                        $result->quantity_active = $quantity_active + $quantityReturnDB;
                        $result->save();
                    }
                }

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'exits',
                    'record_id' => $id,
                    'description' => 'Excluiu uma saída.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Saída removida com sucesso.',
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