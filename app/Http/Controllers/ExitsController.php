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
                    // $query->whereIn('fk_category_id', $categoryUser);
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

            if ($user->level == 'user') {

                $exitRequest = Exits::where('id', $id)->first();

                if ($exitRequest) {
                    $productInExits = $exitRequest->fk_product_equipament_id;
                    $productEspecific = ProductEquipament::where('id', $productInExits)->first();
                    $verifyPresenceProdcutEspecificInCategory = in_array($productEspecific, $categoryUser);

                    if ($verifyPresenceProdcutEspecificInCategory === false) {
                        return response()->json([
                            'sucess' => false,
                            'message' => 'Você não pode ter acesso a um produto que não pertence ao seu setor.'
                        ]);
                    }
                }

                $exit = Exits::with(['productEquipament.category'])
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

                if ($exitDataUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma saida encontrada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Saída recuperada com sucesso.',
                    'data' => $exitDataUser,
                ]);
            }

            $exit = Exits::with(['productEquipament.category'])
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

            $exitDataAdm = [
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

            if ($exitDataAdm == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma saida encontrada.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Saída recuperada com sucesso.',
                'data' => $exitDataAdm,
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

            $productEquipamentUser = ProductEquipament::where('id', $id)->first();

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

            if ($request->fk_product_equipament_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Divergência na identifição do produto/equipamento.',
                ]);
            }

            $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $id)->sum('quantity');
            $quantityTotalExits = Exits::where('fk_product_equipament_id', $id)->sum('quantity');

            $quantityTotalReservation = 0;

            if (in_array(1, $categoryUser, true) || in_array(5, $categoryUser, true)) {
                $quantityTotalReservation = Reservation::where('reservation_finished', 'false')
                    ->where('date_finished', 'false')
                    ->sum('quantity');
            }

            // dd($quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityTotalReservation), $quantityTotalInputs, $quantityTotalExits, $quantityTotalReservation);
            $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityTotalReservation);

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

            if ($validateData) {
                $exits = Exits::create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'fk_user_id' => $request->fk_user_id,
                    'reason_project' => $request->reason_project,
                    'observation' => $request->observation,
                    'quantity' => $request->quantity,
                    'withdrawal_date' => $request->withdrawal_date,
                    'delivery_to' => $request->delivery_to,
                ]);
            }

            $date = now();

            if (isset($exits)) {

                $newQuantityTotal = $quantityTotalProduct - $exits['quantity'];

                if ($newQuantityTotal <= $productQuantityMin) {

                    $updateInputExists = false;
                    $insertInput = false;

                    $productAlert = DB::table('product_alerts')
                        ->where('fk_product_equipament_id', $id)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($productAlert) {

                        $updateInputExists = DB::table('product_alerts')
                            ->where('fk_product_equipament_id', $id)
                            ->update([
                                'quantity_min' => $productQuantityMin,
                                'fk_category_id' => $productEquipamentUser->fk_category_id,
                                'created_at' => $date,
                            ]) > 0; // Retorna true se pelo menos uma linha foi afetada
                    } else {
                        $insertInput = DB::table('product_alerts')
                            ->insert([
                                'fk_product_equipament_id' => $id,
                                'quantity_min' => $productQuantityMin,
                                'fk_category_id' => $productEquipamentUser->fk_category_id,
                                'created_at' => $date,
                            ]);
                    }


                    if ($updateInputExists || $updateInputExists == false || $insertInput) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Retirada concluída com sucesso',
                            'data' => $exits,
                        ]);
                    }
                }
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

            $input = Inputs::where("fk_product_equipament_id", $fk_product)->first();

            $quantityTotalProduct = $input->quantity;

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

            if ((int)$quantityOld > (int)$quantityNew) {

                $returnDB = $quantityTotalProduct + ($quantityOld - $quantityNew);
                $input->update(['quantity' => $returnDB]);
            } elseif ((int)$quantityNew > (int)$quantityOld) {

                $removeDB = $quantityNew - $quantityOld;

                if ($quantityTotalProduct < $removeDB) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityTotalProduct . ' unidades disponíveis.',
                    ]);
                }
                $input->update(['quantity' => $quantityTotalProduct - $removeDB]);
            }

            $updateExits->fill($validateData);
            $updateExits->save();

            if ($updateExits->save()) {

                if ($quantityTotalProduct <= $product->quantity_min) {

                    $productAlert = DB::table('product_alerts')->where('fk_product_equipament_id', $fk_product)->first();

                    if ($productAlert) {
                        DB::table('product_alerts')->where('fk_product_equipament_id', $fk_product)->update([
                            'quantity_total' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                        ]);
                    } else {
                        DB::table('product_alerts')->insert([
                            'fk_product_equipament_id' => $fk_product,
                            'quantity_total' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Saída atualizada com sucesso.',
                    'data' => $updateExits,
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
            $input = Inputs::where('fk_product_equipament_id', $idProduct)->first();

            if ($product) {

                $input = Inputs::where('fk_product_equipament_id', $idProduct)->first();

                if ($input) {
                    $quantityTotalDB = $input->quantity;
                    $product->update(['quantity' => $quantityTotalDB + $quantityReturnStock]);
                }
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