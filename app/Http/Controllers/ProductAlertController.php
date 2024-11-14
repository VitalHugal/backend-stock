<?php

namespace App\Http\Controllers;

use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductAlert;
use App\Models\ProductEquipament;
use App\Models\Reservation;
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
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($user->level == 'user') {

                $quantityReserveNotFinished = Reservation::where('reservation_finished', 0)
                    ->where('fk_user_id_finished', null)
                    ->sum('quantity');

                $productAlertUser = ProductEquipament::with('category', 'inputs')
                    ->whereIn('fk_category_id', $categoryUser)
                    ->get()
                    ->filter(function ($product) use ($quantityReserveNotFinished) {
                        $productEquipamentId = $product->id;

                        $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                        $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');

                        $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                        return $quantityTotalProduct <= $product->quantity_min;
                    })
                    ->map(function ($product) use ($quantityReserveNotFinished) {
                        $productEquipamentId = $product->id;

                        $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                        $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                        $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity_stock' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'name-category' => $product->category ? $product->category->name : null,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'deleted_at' => $product->deleted_at,
                        ];
                    })->values();

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $productAlertUser,
                ]);
            }

            $quantityReserveNotFinished = Reservation::where('reservation_finished', 0)
                ->where('fk_user_id_finished', null)
                ->sum('quantity');

            $productAlertAll = ProductEquipament::with('category', 'inputs')->get()
                ->filter(function ($product) use ($quantityReserveNotFinished) {
                    $productEquipamentId = $product->id;

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return $quantityTotalProduct <= $product->quantity_min;
                })
                ->map(function ($product) use ($quantityReserveNotFinished) {
                    $productEquipamentId = $product->id;

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'name-category' => $product->category ? $product->category->name : null,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'deleted_at' => $product->deleted_at,
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
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