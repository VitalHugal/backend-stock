<?php

namespace App\Http\Controllers;

use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\table;
use function PHPUnit\Framework\isEmpty;

class ExitsController extends CrudController
{
    protected $exits;

    public function __construct(Exits $exits)
    {
        parent::__construct($exits);

        $this->exits = $exits;
    }

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

            if ($user->level !== 'admin' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($level == 'user') {
                $exits = Exits::with(['productEquipament.category', "user"])
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                // Transformando os itens dentro da paginação
                $exits->getCollection()->transform(function ($exit) {

                    return [
                        'id' => $exit->id,
                        'fk_user_id' => $exit->fk_user_id,
                        'name_user_exits' => $exit->user->name,
                        'reason_project' => $exit->reason_project,
                        'observation' => $exit->observation,
                        'quantity' => $exit->quantity,
                        'delivery_to' => $exit->delivery_to,
                        'product_name' => $exit->productEquipament->name,
                        'id_product' => $exit->productEquipament->id,
                        'category_name' => $exit->productEquipament->category->name,
                        'created_at' => $this->exits->getFormattedDate($exit, 'created_at'),
                        'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at'),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Saídas recuperadas com sucesso.',
                    'data' => $exits,
                ]);
            }

            $exitsAdmin = Exits::with(['productEquipament.category', "user"])
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Transformando os itens dentro da paginação
            $exitsAdmin->getCollection()->transform(function ($exit) {

                return [
                    'id' => $exit->id,
                    'fk_user_id' => $exit->fk_user_id,
                    'name_user_exits' => $exit->user->name,
                    'reason_project' => $exit->reason_project,
                    'observation' => $exit->observation,
                    'quantity' => $exit->quantity,
                    'delivery_to' => $exit->delivery_to,
                    'product_name' => $exit->productEquipament->name,
                    'id_product' => $exit->productEquipament->id,
                    'category_name' => $exit->productEquipament->category->name,
                    'created_at' => $this->exits->getFormattedDate($exit, 'created_at'),
                    'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at'),
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


            if ($user->level !== 'admin' && $categoryUser == null) {
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
                $exit = Exits::with(['productEquipament.category', "user"])
                    ->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })->first();

                if (!$exit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saída não encontrada.',
                    ]);
                }

                $exitDataUser = [
                    'id' => $exit->id,
                    'fk_user_id' => $exit->fk_user_id,
                    'name_user_exits' => $exit->user->name,
                    'reason_project' => $exit->reason_project,
                    'observation' => $exit->observation,
                    'quantity' => $exit->quantity,
                    'delivery_to' => $exit->delivery_to,
                    'product_name' => $exit->productEquipament ? $exit->productEquipament->name : null,
                    'id_product' => $exit->productEquipament->id,
                    'category_name' => $exit->productEquipament->category->name,
                    'created_at' => $this->exits->getFormattedDate($exit, 'created_at'),
                    'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at'),
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Saída recuperada com sucesso.',
                    'data' => $exitDataUser,
                ]);
            }
            $exit = Exits::with(['productEquipament.category', "user"])
                ->where('id', $id)
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    // $query->whereIn('fk_category_id', $categoryUser);
                })->first();

            if (!$exit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saída não encontrada.',
                ]);
            }

            $exitDataAdmin = [
                'id' => $exit->id,
                'fk_user_id' => $exit->fk_user_id,
                'name_user_exits' => $exit->user->name,
                'reason_project' => $exit->reason_project,
                'observation' => $exit->observation,
                'quantity' => $exit->quantity,
                'delivery_to' => $exit->delivery_to,
                'product_name' => $exit->productEquipament ? $exit->productEquipament->name : null,
                'id_product' => $exit->productEquipament->id,
                'category_name' => $exit->productEquipament->category->name,
                'created_at' => $this->exits->getFormattedDate($exit, 'created_at'),
                'updated_at' => $this->exits->getFormattedDate($exit, 'updated_at'),
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

            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $productEquipamentUser = ProductEquipament::where('id', $request->fk_product_equipament_id)->first();

            (int)$productQuantityMin = $productEquipamentUser->quantity_min;

            if ($user->level == 'user') {
                $validationExits = in_array($productEquipamentUser->fk_category_id, $categoryUser);
                if ($validationExits === false) {
                    return response()->json([
                        'sucess' => false,
                        'message' => 'Você não pode realizar saida de produtos que não pertence ao seu setor.'
                    ]);
                }
            }

            if ($productEquipamentUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
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
                    'message' => 'Produto indisponível.',
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

            if ($validateData) {
                $exits = Exits::create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'fk_user_id' => $idUser,
                    'reason_project' => $request->reason_project,
                    'observation' => $request->observation,
                    'quantity' => $request->quantity,
                    'delivery_to' => $request->delivery_to,
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
                    'message' => 'Retirada concluída com sucesso',
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

            if ($user->level !== 'admin' && $categoryUser == null) {
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
                $updateExits->update(['quantity' => $updateExits->quantity + $returnDB]);

                // Log::info("User nº:{$idUser} updates quantity from product in exit nº:{$id}. Returned {$returnDB} unit for bank of data.");

            } elseif ((int)$quantityNew > (int)$quantityOld) {
                $removeDB = $quantityNew - $quantityOld;

                if ($quantityTotalProduct < $removeDB) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalProduct . ' unidades disponíveis.',
                    ]);
                }
                $updateExits->update(['quantity' => $updateExits->quantity - $removeDB]);
                // Log::info("User nº:{$idUser} updates quantity from product in exit nº:{$id}. Removed {$removeDB} unit for bank of data.");
            }

            $updateExits->fill($validateData);
            $updateExits->save();

            // Verificando as mudanças e criando a string de log
            $changes = $updateExits->getChanges(); // Retorna apenas os campos que foram alterados
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
                    'message' => 'Retirada atualizada com sucesso',
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

            if ($level == 'user') {
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

            $deleteExits->delete();

            if ($deleteExits) {

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