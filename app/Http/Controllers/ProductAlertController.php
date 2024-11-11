<?php

namespace App\Http\Controllers;

use App\Models\ProductAlert;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAlertController extends CrudController
{
    protected $product_alert;

    public function __construct(ProductAlert $product_alert)
    {
        parent::__construct($product_alert);

        $this->product_alert = $product_alert;
    }

    public function getAllProductAlert(Request $request)
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
                
                $productAlertUser = ProductAlert::with('category')
                    ->whereIn('fk_category_id', $categoryUser)
                    ->get()
                    ->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            // 'quantity' => $product->quantity,
                            'quantity_min' => $product->quantity_min,
                            'name-category' => $product->category ? $product->category->name : null,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'deleted_at' => $product->deleted_at,
                        ];
                    });

                if ($productAlertUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado para a(s) categoria(s) do usuário.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $productAlertUser,
                ]);
            }

            $productAlertAll = ProductAlert::with('category', 'productEquipament')->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                            'name' => $product->productEquipament->name ?? null,
                            // 'quantity' => $product->productEquipament->quantity,
                            'quantity_min' => $product->productEquipament->quantity_min,
                            'name-category' => $product->category ? $product->category->name : null,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'deleted_at' => $product->deleted_at,
                    ];
                });

            if ($productAlertAll == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado para a(s) categoria(s) do usuário.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto(s)/Equipamento(s) em alerta recuperados com sucesso.',
                'data' => $productAlertAll,
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