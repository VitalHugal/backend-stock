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
use Illuminate\Pagination\LengthAwarePaginator;
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


            if ($user->level == 'user') {

                $productAlertUser = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->whereIn('fk_category_id', $categoryUser)
                    ->orderBy('fk_category_id', 'asc')
                    ->get();

                // $filteredCollection = $productAlertUser->getCollection()->transform(function ($product) {

                //     $productEquipamentId = $product->id;

                //     $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                //     $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                //     $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $productEquipamentId)
                //         ->where('reservation_finished', false)
                //         ->whereNull('date_finished')
                //         ->whereNull('fk_user_id_finished')
                //         ->sum('quantity');

                //     $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                //     if ($quantityTotalProduct <= $product->quantity_min) {
                //         return [
                //             'id' => $product->id,
                //             'name' => $product->name,
                //             'quantity_stock' => $quantityTotalProduct,
                //             'quantity_min' => $product->quantity_min,
                //             'name-category' => $product->category->name,
                //             'created_at' => $this->product_alert->getFormattedDate($product, 'created_at'),
                //             'updated_at' => $this->product_alert->getFormattedDate($product, 'updated_at'),
                //         ];
                //     }
                //     return null;
                // })->filter()->values();

                // // Recria a paginação
                // $paginated = new LengthAwarePaginator(
                //     $filteredCollection, // Coleção filtrada
                //     count($filteredCollection), // Total de itens antes do filtro (para manter a paginação correta)
                //     $productAlertUser->perPage(), // Itens por página
                //     $productAlertUser->currentPage(), // Página atual
                //     ['path' => request()->url(), 'query' => request()->query()] // Mantém a URL e query string
                // );

                // // dd($productAlertUser->perPage());
                // return response()->json([
                //     'success' => true,
                //     'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                //     'data' => $paginated,
                // ]);

                $filteredCollectionUser = $productAlertUser->map(function ($product) {
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

                    if ($quantityTotalProduct <= $product->quantity_min) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity_stock' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'name-category' => $product->category->name ?? 'Sem Categoria',
                            'created_at' => $this->product_alert->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->product_alert->getFormattedDate($product, 'updated_at'),
                        ];
                    }
                    return null;
                })->filter()->values(); // Remove os valores nulos

                // Recria a paginação com base na coleção filtrada
                $page = request()->get('page', 1); // Página atual
                $perPage = 10; // Itens por página
                $paginatedUser = new LengthAwarePaginator(
                    $filteredCollectionUser->forPage($page, $perPage), // Itens da página atual
                    $filteredCollectionUser->count(), // Total de itens após filtro
                    $perPage, // Itens por página
                    $page, // Página atual
                    ['path' => request()->url(), 'query' => request()->query()] // Mantém a URL e query string
                );

                // Retorna a resposta com a coleção paginada
                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $paginatedUser,
                ]);
            }

            if ($user->level == 'admin' || $user->level == 'manager') {

                //     $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                //         $query->whereNull('deleted_at');
                //     }])
                //         ->whereHas('category', function ($query) {
                //             $query->whereNull('deleted_at');
                //         })
                //         ->where('is_group', 0)
                //         ->orderBy('fk_category_id', 'asc')
                //         ->get();

                //     $filteredCollectionAdmin = $productAllAdmin->getCollection()->transform(function ($product) {
                //         $productEquipamentId = $product->id;

                //         // Calcula as quantidades totais
                //         $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                //         $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                //         $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $productEquipamentId)
                //             ->where('reservation_finished', false)
                //             ->whereNull('date_finished')
                //             ->whereNull('fk_user_id_finished')
                //             ->sum('quantity');

                //         $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                //         if ($quantityTotalProduct <= $product->quantity_min) {
                //             return [
                //                 'id' => $product->id,
                //                 'name' => $product->name,
                //                 'quantity_stock' => $quantityTotalProduct,
                //                 'quantity_min' => $product->quantity_min,
                //                 'name-category' => $product->category->name,
                //                 'created_at' => $this->product_alert->getFormattedDate($product, 'created_at'),
                //                 'updated_at' => $this->product_alert->getFormattedDate($product, 'updated_at'),
                //             ];
                //         }
                //         return null;
                //     })->filter()->values();

                //     // Recria a paginação
                //     $paginatedAdmin = new LengthAwarePaginator(
                //         $filteredCollectionAdmin, // Coleção filtrada
                //         $productAllAdmin->total(), // Total de itens antes do filtro (para manter a paginação correta)
                //         $productAllAdmin->perPage(), // Itens por página
                //         $productAllAdmin->currentPage(), // Página atual
                //         ['path' => request()->url(), 'query' => request()->query()] // Mantém a URL e query string
                //     );

                //     return response()->json([
                //         'success' => true,
                //         'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                //         'data' => $paginatedAdmin,
                //     ]);

                $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->where('is_group', 0)
                    ->orderBy('fk_category_id', 'asc')
                    ->get(); // Use get() em vez de paginate()

                // Filtrar a coleção com base nos cálculos necessários
                $filteredCollectionAdmin = $productAllAdmin->map(function ($product) {
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

                    if ($quantityTotalProduct <= $product->quantity_min) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity_stock' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'name-category' => $product->category->name ?? 'Sem Categoria',
                            'created_at' => $this->product_alert->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->product_alert->getFormattedDate($product, 'updated_at'),
                        ];
                    }
                    return null;
                })->filter()->values(); // Remove os valores nulos

                // Recria a paginação com base na coleção filtrada
                $page = request()->get('page', 1); // Página atual
                $perPage = 10; // Itens por página
                $paginatedAdmin = new LengthAwarePaginator(
                    $filteredCollectionAdmin->forPage($page, $perPage)->values(), // Itens da página atual
                    $filteredCollectionAdmin->count(), // Total de itens após filtro
                    $perPage, // Itens por página
                    $page, // Página atual
                    ['path' => request()->url(), 'query' => request()->query()]
                );

                // dd($paginatedAdmin);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $paginatedAdmin,
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