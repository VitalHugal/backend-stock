<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductEquipament;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductEquipamentController extends CrudController
{
    protected $productEquipaments;

    public function __construct(ProductEquipament $productEquipament)
    {
        parent::__construct($productEquipament);

        $this->productEquipaments = $productEquipament;
    }

    public function getAllProductEquipament(Request $request)
    {
        try {
            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id');

            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }


            if ($user->level == 'user') {
                // Busca todos os produtos com o nome da categoria relacionado
                $productEquipamentUser = ProductEquipament::with('category')
                    ->whereIn('fk_category_id', $categoryUser)
                    ->get()
                    ->map(function ($product) {
                        return [
                            'name-category' => $product->category ? $product->category->name : null,
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity_' => $product->quantity,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'deleted_at' => $product->deleted_at,
                        ];
                    });

                if ($productEquipamentUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado para a(s) categoria(s) do usuário.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) recuperados com sucesso.',
                    'data' => $productEquipamentUser,
                ]);
            }

            $productAll = ProductEquipament::with('category')->get()
                ->map(function ($product) {
                    return [
                        'name-category' => $product->category ? $product->category->name : null,
                        'id' => $product->id,
                        'name' => $product->name,
                        // 'quantity' => $product->quantity,
                        'quantity_min' => $product->quantity_min,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'deleted_at' => $product->deleted_at,
                    ];
                });

            if ($productAll == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado para a(s) categoria(s) do usuário.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto(s)/Equipamento(s) recuperados com sucesso.',
                'data' => $productAll,
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

    public function getIdProductEquipament(Request $request, $id)
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

                // Busca todos os produtos com o nome da categoria relacionado
                $productEquipamentUser = ProductEquipament::with('category')
                    ->whereIn('fk_category_id', $categoryUser)->where('id', $id)
                    ->get()
                    ->map(function ($product) {
                        return [
                            'name-category' => $product->category ? $product->category->name : null,
                            'id' => $product->id,
                            'name' => $product->name,
                            // 'quantity' => $product->quantity,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'deleted_at' => $product->deleted_at,
                        ];
                    });

                if ($productEquipamentUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Produto/Equipamentos recuperados com sucesso.',
                    'data' => $productEquipamentUser,
                ]);
            }

            $productAdmin = ProductEquipament::with('category')
                ->where('id', $id)
                ->get()
                ->map(function ($product) {
                    return [
                        'name-category' => $product->category ? $product->category->name : null,
                        'id' => $product->id,
                        'name' => $product->name,
                        // 'quantity' => $product->quantity,
                        'quantity_min' => $product->quantity_min,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'deleted_at' => $product->deleted_at,
                    ];
                });

            if ($productAdmin == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto/Equipamentos recuperados com sucesso.',
                'data' => $productAdmin,
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
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id');

            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $createProductEquipaments = $request->validate(
                $this->productEquipaments->rulesProductEquipamentos(),
                $this->productEquipaments->feedbackProductEquipaments()
            );

            $name = $request->name;
            $quantity = $request->quantity;
            $quantity_min = $request->quantity_min;
            $fk_category_id = $request->fk_category_id;

            $createProductEquipaments = $this->productEquipaments->create([
                'name' => $name,
                // 'quantity' => $quantity,
                'quantity_min' => $quantity_min,
                'fk_category_id' => $fk_category_id,
            ]);

            if ($createProductEquipaments) {
                return response()->json([
                    'success' => true,
                    'message' => "Cadastrado com sucesso.",
                    'data' => $createProductEquipaments,
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
            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id');

            if ($user->level !== 'admin' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $updateProductEquipaments = $this->productEquipaments->find($id);

            if (!$updateProductEquipaments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $validatedData = $request->validate(
                $this->productEquipaments->rulesProductEquipamentos(),
                $this->productEquipaments->feedbackProductEquipaments(),
            );

            $updateProductEquipaments->fill($validatedData);
            $updateProductEquipaments->save();

            if ($updateProductEquipaments) {
                return response()->json([
                    'success' => true,
                    'message' => 'Atualizado com sucesso.',
                    'data' => $updateProductEquipaments,
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

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteProductEquipaments = $this->productEquipaments->find($id);

            if (!$deleteProductEquipaments) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteProductEquipaments->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produto/Equipamento removido com sucesso.',
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