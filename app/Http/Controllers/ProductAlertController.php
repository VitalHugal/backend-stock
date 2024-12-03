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
use PHPUnit\TextUI\XmlConfiguration\RemoveListeners;

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

                $productAlertUser = ProductEquipament::with('category', 'inputs')
                    ->whereIn('fk_category_id', $categoryUser)
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10);

                $productAlertUser->getCollection()->transform(function ($product) {

                    $productEquipamentId = $product->id;

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $productEquipamentId)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    $created_at = 'created_at';
                    $updated_at = 'updated_at';

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'name-category' => $product->category->name ?? null,
                        'created_at' => $this->product_alert->getFormattedDate($product, $created_at),
                        'updated_at' => $this->product_alert->getFormattedDate($product, $updated_at),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $productAlertUser,
                ]);
            }

            if ($user->level == 'admin') {

                $productAllAdmin = ProductEquipament::with(['category'])
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10);

                $productAllAdmin->getCollection()->transform(function ($product) {
                    $productEquipamentId = $product->id;

                    // Calcula as quantidades totais
                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $productEquipamentId)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    $created_at = 'created_at';
                    $updated_at = 'updated_at';

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'name-category' => $product->category->name ?? null,
                        'created_at' => $this->product_alert->getFormattedDate($product, $created_at),
                        'updated_at' => $this->product_alert->getFormattedDate($product, $updated_at),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $productAllAdmin,
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