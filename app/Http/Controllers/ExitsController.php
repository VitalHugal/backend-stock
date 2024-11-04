<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\ProductEquipament;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            // Verifica o nível de acesso e filtra as saídas
            if ($level == 'user') {

                $exits = Exits::with(['productEquipament.category'])
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->get()
                    ->map(function ($exit) {
                        return [
                            'exit_id' => $exit->id,
                            'fk_user_id' => $exit->fk_user_id,
                            'reason_project' => $exit->reason_project,
                            'observation' => $exit->observation,
                            'quantity' => $exit->quantity,
                            'withdrawal_date' => $exit->withdrawal_date,
                            'delivery_to' => $exit->delivery_to,
                            'created_at' => $exit->created_at,
                            'updated_at' => $exit->updated_at,
                            'product_name' => $exit->productEquipament->name,
                            'category_name' => $exit->productEquipament->category->name,
                        ];
                    });

                if ($exits == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma saida encontrada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Saídas recuperadas com sucesso.',
                    'data' => $exits,
                ]);
            }

            $exitsAdmin = Exits::with(['productEquipament.category'])
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    $query->whereIn('fk_category_id', $categoryUser);
                })
                ->get()
                ->map(function ($exit) {
                    return [
                        'exit_id' => $exit->id,
                        'fk_user_id' => $exit->fk_user_id,
                        'reason_project' => $exit->reason_project,
                        'observation' => $exit->observation,
                        'quantity' => $exit->quantity,
                        'withdrawal_date' => $exit->withdrawal_date,
                        'delivery_to' => $exit->delivery_to,
                        'created_at' => $exit->created_at,
                        'updated_at' => $exit->updated_at,
                        'product_name' => $exit->productEquipament->name,
                        'category_name' => $exit->productEquipament->category->name,
                    ];
                });

            if ($exitsAdmin == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma saida encontrada.',
                ]);
            }

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

            $exit = Exits::with(['productEquipament.category'])
                ->where('id', $id) // Filtra pelo ID da saída específico
                ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                    $query->whereIn('fk_category_id', $categoryUser);
                })
                ->first(); // Retorna apenas um registro

            if (!$exit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saída não encontrada.',
                ]);
            }

            $exitData = [
                'exit_id' => $exit->id,
                'fk_user_id' => $exit->fk_user_id,
                'reason_project' => $exit->reason_project,
                'observation' => $exit->observation,
                'quantity' => $exit->quantity,
                'withdrawal_date' => $exit->withdrawal_date,
                'delivery_to' => $exit->delivery_to,
                'created_at' => $exit->created_at,
                'updated_at' => $exit->updated_at,
                'product_name' => $exit->productEquipament ? $exit->productEquipament->name : null,
                'category_name' => $exit->productEquipament->category->name,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Saída recuperada com sucesso.',
                'data' => $exitData,
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

    public function exits(Request $request, $id)
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

            $productEquipamentUser = ProductEquipament::with('category')
                ->whereIn('fk_category_id', $categoryUser)->where('id', $id)->first();


            if ($productEquipamentUser->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
            }

            //dd($productEquipamentUser);

            // $date = now();
            $quantityProductEquipament = $productEquipamentUser->quantity;

            $numQuantity = intval($request->quantity);

            if ($numQuantity > $quantityProductEquipament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityProductEquipament . ' unidades disponíveis.',
                ]);
            }

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

            $newQuantityProductEquipament = $quantityProductEquipament - $numQuantity;

            if ($request->fk_product_equipament_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Divergência na identifição do produto/equipamento.',
                ]);
            }

            $exits = Exits::create([
                'fk_product_equipament_id' => $request->fk_product_equipament_id,
                'fk_user_id' => $request->fk_user_id,
                'reason_project' => $request->reason_project,
                'observation' => $request->observation,
                'quantity' => $request->quantity,
                'withdrawal_date' => $request->withdrawal_date,
                'delivery_to' => $request->delivery_to,
            ]);

            if ($exits) {
                ProductEquipament::where('id', $id)->update(['quantity' => $newQuantityProductEquipament]);

                return response()->json([
                    'success' => true,
                    'message' => 'Retirada concluída com sucesso',
                    'data' => $exits,
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

    public function updateExits(Request $request, $id)
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

            $updateExits = $this->exits->find($id);
            
            if (!$updateExits) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma saída encontrada.',
                ]);
            }

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

            $quantityTotalDB = $product->quantity;

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
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

            $updateExits->fill($validateData);
            $updateExits->save();

            return response()->json([
                'success' => true,
                'message' => 'Saída atualizada com sucesso.',
                'data' => $updateExits,
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

            $deleteExits = $this->exits->find($id);

            if (!$deleteExits) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $quantityReturnStock = $deleteExits->quantity;
            $idProduct = $deleteExits->fk_product_equipament_id;

            $deleteExits->delete();

            $product = ProductEquipament::where('id', $idProduct)->first();

            if ($product) {
                $quantityTotalDB = $product->quantity;
                $product->update(['quantity' => $quantityTotalDB + $quantityReturnStock]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Saída removida com sucesso.',
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