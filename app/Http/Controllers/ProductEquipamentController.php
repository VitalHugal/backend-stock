<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($user->level == 'user') {

                if ($request->has('name') && $request->input('name') != '' && $request->has('active') && $request->input('active') == 'true') {

                    $productEquipamentUserSearch = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })

                        ->whereIn('fk_category_id', $categoryUser)
                        ->where('name', 'like', '%' . $request->input('name') . '%')
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);

                    if ($productEquipamentUserSearch->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nenhum produto encontrado com o nome informado.',
                        ]);
                    }

                    $productEquipamentUserSearch->getCollection()->transform(function ($product) {

                        $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                        $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                        $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                            ->where('reservation_finished', false)
                            ->whereNull('date_finished')
                            ->whereNull('fk_user_id_finished')
                            ->sum('quantity');

                        $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                        return [
                            'id' => $product->id,
                            'name-category' => $product->category && $product->category->trashed()
                                ? $product->category->name . ' (Deletado)'
                                : $product->category->name ?? null,

                            'name' => $product && $product->trashed()
                                ? $product->name . ' (Deletado)'
                                : $product->name ?? null,
                            'quantity_stock' => $quantityTotalProduct,
                            'expiration_date' => $product->expiration_date,
                            'observation' => $product->observation,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                            'deleted_at' => $product && $product->trashed()
                                ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                                : $product->deleted_at ?? null,
                        ];
                    });

                    return response()->json([
                        'success' => true,
                        'message' => 'Produto(s)/Equipamento(s) pesquisado recuperados com sucesso.',
                        'data' => $productEquipamentUserSearch,
                    ]);
                }

                if ($request->has('expiration_date') && $request->input('expiration_date') == '1' || $request->input('expiration_date') == '0' || $request->input('expiration_date') == '' && $request->has('category') && $request->input('category') != '' && $request->has('active') && $request->input('active') == 'true') {

                    $productEquipamentUserSearch = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })
                        ->when($request->has('category'), function ($query) use ($request) {
                            $query->where('fk_category_id', $request->input('category'));
                        })
                        ->when($request->has('expiration_date') && in_array($request->input('expiration_date'), ['0', '1']), function ($query) use ($request) {
                            $query->where('expiration_date', $request->input('expiration_date'));
                        })
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['category' => $request->input('category'), 'active' => $request->input('active')]);


                    if ($productEquipamentUserSearch->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nenhum produto encontrado com o nome informado.',
                        ]);
                    }

                    $productEquipamentUserSearch->getCollection()->transform(function ($product) {

                        $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                        $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                        $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                            ->where('reservation_finished', false)
                            ->whereNull('date_finished')
                            ->whereNull('fk_user_id_finished')
                            ->sum('quantity');

                        $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                        return [
                            'id' => $product->id,
                            'name-category' => $product->category && $product->category->trashed()
                                ? $product->category->name . ' (Deletado)'
                                : $product->category->name ?? null,

                            'name' => $product && $product->trashed()
                                ? $product->name . ' (Deletado)'
                                : $product->name ?? null,
                            'quantity_stock' => $quantityTotalProduct,
                            'expiration_date' => $product->expiration_date,
                            'observation' => $product->observation,
                            'quantity_min' => $product->quantity_min,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                            'deleted_at' => $product && $product->trashed()
                                ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                                : $product->deleted_at ?? null,
                        ];
                    });

                    return response()->json([
                        'success' => true,
                        'message' => 'Produto(s)/Equipamento(s) em pesquisa por ativos e setor recuprados com sucesso.',
                        'data' => $productEquipamentUserSearch,
                    ]);
                }

                if ($request->has('active') && $request->input('active') == 'true') {
                    $productEquipamentUser = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })
                        ->whereIn('fk_category_id', $categoryUser)
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['active' => $request->input('active')]);
                } elseif ($request->has('active') && $request->input('active') == 'false') {
                    $productEquipamentUser = ProductEquipament::with(['category' => function ($query) {
                        $query->withTrashed();
                    }])
                        ->withTrashed()
                        ->whereIn('fk_category_id', $categoryUser)
                        ->whereNotNull('deleted_at')
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['active' => request()->input('active')]);
                } else {
                    $productEquipamentUser = ProductEquipament::with(['category' => function ($query) {
                        $query->withTrashed();
                    }])
                        ->withTrashed()
                        ->whereIn('fk_category_id', $categoryUser)
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10);
                }

                // filto para buscar todos os produtos ativos e que estão em alerta no estoque
                if ($request->has('active') && $request->input('active') == 'true' && $request->has('products_alert') && $request->input('products_alert') == 'true') {
                    $productEquipamentUser = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })
                        ->whereIn('fk_category_id', $categoryUser)
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['active' => $request->input('active')]);

                    $filteredCollectionUser = $productEquipamentUser->getCollection()->transform(function ($product) {
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
                                'name-category' => $product->category && $product->category->trashed()
                                    ? $product->category->name . ' (Deletado)'
                                    : $product->category->name ?? null,
                                'name' => $product && $product->trashed()
                                    ? $product->name . ' (Deletado)'
                                    : $product->name ?? null,
                                'quantity_stock' => $quantityTotalProduct,
                                'quantity_min' => $product->quantity_min,
                                'expiration_date' => $product->expiration_date,
                                'observation' => $product->observation,
                                'fk_category_id' => $product->fk_category_id,
                                'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                                'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                                'deleted_at' => $product && $product->trashed()
                                    ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                                    : $product->deleted_at ?? null,
                            ];
                        }
                        return null;
                    })->filter()->values();

                    // Recria a paginação
                    $paginatedUser = new LengthAwarePaginator(
                        $filteredCollectionUser, // Coleção filtrada
                        $productEquipamentUser->total(), // Total de itens antes do filtro (para manter a paginação correta)
                        $productEquipamentUser->perPage(), // Itens por página
                        $productEquipamentUser->currentPage(), // Página atual
                        ['path' => request()->url(), 'query' => request()->query()] // Mantém a URL e query string
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                        'data' => $paginatedUser,
                    ]);
                }

                $productEquipamentUser->getCollection()->transform(function ($product) {

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name-category' => $product->category && $product->category->trashed()
                            ? $product->category->name . ' (Deletado)'
                            : $product->category->name ?? null,
                        'name' => $product && $product->trashed()
                            ? $product->name . ' (Deletado)'
                            : $product->name ?? null,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'expiration_date' => $product->expiration_date,
                        'observation' => $product->observation,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                        'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                        'deleted_at' => $product && $product->trashed()
                            ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                            : $product->deleted_at ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) recuperados com sucesso.',
                    'data' => $productEquipamentUser,
                ]);
            }

            //ADMIN OR MANAGER

            if ($request->has('expiration_date') && $request->input('expiration_date') == '1' || $request->input('expiration_date') == '0' || $request->input('expiration_date') == '' && $request->has('category') && $request->input('category') != '' && $request->has('active') && $request->input('active') == 'true') {

                $productEquipamentAdminSearch = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->when($request->has('category'), function ($query) use ($request) {
                        $query->where('fk_category_id', $request->input('category'));
                    })
                    ->when($request->has('expiration_date') && in_array($request->input('expiration_date'), ['0', '1']), function ($query) use ($request) {
                        $query->where('expiration_date', $request->input('expiration_date'));
                    })
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10)
                    ->appends(['category' => $request->input('category'), 'active' => $request->input('active')]);


                if ($productEquipamentAdminSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado com o nome informado.',
                    ]);
                }

                $productEquipamentAdminSearch->getCollection()->transform(function ($product) {

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name-category' => $product->category && $product->category->trashed()
                            ? $product->category->name . ' (Deletado)'
                            : $product->category->name ?? null,

                        'name' => $product && $product->trashed()
                            ? $product->name . ' (Deletado)'
                            : $product->name ?? null,
                        'quantity_stock' => $quantityTotalProduct,
                        'expiration_date' => $product->expiration_date,
                        'observation' => $product->observation,
                        'quantity_min' => $product->quantity_min,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                        'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                        'deleted_at' => $product && $product->trashed()
                            ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                            : $product->deleted_at ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em pesquisa por ativos e setor recuprados com sucesso.',
                    'data' => $productEquipamentAdminSearch,
                ]);
            }

            // filtro para buscar todos os produtos ativos
            if ($request->has('active') && $request->input('active') == 'true') {
                $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10)
                    ->appends(['active' => $request->input('active')]);
            }

            //filtro para buscar todos os produtos deletados
            elseif ($request->has('active') && $request->input('active') == 'false') {
                $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                    $query->withTrashed();
                }])
                    ->withTrashed()
                    ->whereNotNull('deleted_at')
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10)
                    ->appends(['active' => request()->input('active')]);
            }

            // filtro para buscar todos os produtos incluindo deltados
            else {
                $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                    $query->withTrashed();
                }])
                    ->withTrashed()
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10);
            }

            // filto para buscar todos os produtos ativos e que estão em alerta no estoque
            if ($request->has('active') && $request->input('active') == 'true' && $request->has('products_alert') && $request->input('products_alert') == 'true') {
                $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10)
                    ->appends(['active' => $request->input('active')]);

                $filteredCollectionAdmin = $productAllAdmin->getCollection()->transform(function ($product) {
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
                            'name-category' => $product->category && $product->category->trashed()
                                ? $product->category->name . ' (Deletado)'
                                : $product->category->name ?? null,
                            'name' => $product && $product->trashed()
                                ? $product->name . ' (Deletado)'
                                : $product->name ?? null,
                            'quantity_stock' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'expiration_date' => $product->expiration_date,
                            'observation' => $product->observation,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                            'deleted_at' => $product && $product->trashed()
                                ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                                : $product->deleted_at ?? null,
                        ];
                    }
                    return null;
                })->filter()->values();

                // Recria a paginação
                $paginatedAdmin = new LengthAwarePaginator(
                    $filteredCollectionAdmin, // Coleção filtrada
                    $productAllAdmin->total(), // Total de itens antes do filtro (para manter a paginação correta)
                    $productAllAdmin->perPage(), // Itens por página
                    $productAllAdmin->currentPage(), // Página atual
                    ['path' => request()->url(), 'query' => request()->query()] // Mantém a URL e query string
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) em alerta recuperado com sucesso.',
                    'data' => $paginatedAdmin,
                ]);
            }

            if ($request->has('name') && $request->input('name') != '' && $request->has('active') &&  $request->input('active') == 'true') {

                $productAllAdminSearch = ProductEquipament::with(['category' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                    ->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })

                    ->where('name', 'like', '%' . $request->input('name') . '%')
                    // ->orderBy('name', 'asc')
                    ->orderBy('fk_category_id', 'asc')
                    ->paginate(10)
                    ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);

                if ($productAllAdminSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado com o nome informado.',
                    ]);
                }

                $productAllAdminSearch->getCollection()->transform(function ($product) {

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name-category' => $product->category && $product->category->trashed()
                            ? $product->category->name . ' (Deletado)'
                            : $product->category->name ?? null,
                        'name' => $product && $product->trashed()
                            ? $product->name . ' (Deletado)'
                            : $product->name ?? null,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'expiration_date' => $product->expiration_date,
                        'observation' => $product->observation,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                        'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                        'deleted_at' => $product && $product->trashed()
                            ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                            : $product->deleted_at ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Produto(s)/Equipamento(s) pesquisado recuperados com sucesso.',
                    'data' => $productAllAdminSearch,
                ]);
            }

            $productAllAdmin->getCollection()->transform(function ($product) {

                $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');
                $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                    ->where('reservation_finished', false)
                    ->whereNull('date_finished')
                    ->whereNull('fk_user_id_finished')
                    ->sum('quantity');

                $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                    ->where('reservation_finished', false)
                    ->whereNull('date_finished')
                    ->whereNull('fk_user_id_finished')
                    ->sum('quantity');

                return [
                    'id' => $product->id,
                    'name-category' => $product->category && $product->category->trashed()
                        ? $product->category->name . ' (Deletado)'
                        : $product->category->name ?? null,
                    'name' => $product && $product->trashed()
                        ? $product->name . ' (Deletado)'
                        : $product->name ?? null,
                    'quantity_stock' => $quantityTotalProduct,
                    'quantity_min' => $product->quantity_min,
                    'expiration_date' => $product->expiration_date,
                    'observation' => $product->observation,
                    'fk_category_id' => $product->fk_category_id,
                    'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                    'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                    'deleted_at' => $product && $product->trashed()
                        ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                        : $product->deleted_at ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Produto(s)/Equipamento(s) recuperados com sucesso.',
                'data' => $productAllAdmin,
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

            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
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

                $productEquipamentUser = ProductEquipament::with('category')
                    ->whereIn('fk_category_id', $categoryUser)
                    ->where('id', $id)
                    ->withTrashed()
                    ->get()
                    ->map(function ($product) {

                        $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                        $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');

                        $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                            ->where('reservation_finished', false)
                            ->whereNull('date_finished')
                            ->whereNull('fk_user_id_finished')
                            ->sum('quantity');

                        $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                        return [
                            'id' => $product->id,
                            'name-category' => $product->category && $product->category->trashed()
                                ? $product->category->name . ' (Deletado)'
                                : $product->category->name ?? null,

                            'name' => $product && $product->trashed()
                                ? $product->name . ' (Deletado)'
                                : $product->name ?? null,
                            'quantity_stock' => $quantityTotalProduct,
                            'quantity_min' => $product->quantity_min,
                            'expiration_date' => $product->expiration_date,
                            'observation' => $product->observation,
                            'fk_category_id' => $product->fk_category_id,
                            'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                            'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                            'deleted_at' => $product && $product->trashed()
                                ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                                : $product->deleted_at ?? null,
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

            //ADMIN OR MANAGER

            $productAdmin = ProductEquipament::with('category')
                ->where('id', $id)
                ->withTrashed()
                ->get()
                ->map(function ($product) {

                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $product->id)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $product->id)->sum('quantity');
                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $product->id)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name-category' => $product->category && $product->category->trashed()
                            ? $product->category->name . ' (Deletado)'
                            : $product->category->name ?? null,

                        'name' => $product && $product->trashed()
                            ? $product->name . ' (Deletado)'
                            : $product->name ?? null,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'expiration_date' => $product->expiration_date,
                        'observation' => $product->observation,
                        'fk_category_id' => $product->fk_category_id,
                        'created_at' => $this->productEquipaments->getFormattedDate($product, 'created_at'),
                        'updated_at' => $this->productEquipaments->getFormattedDate($product, 'updated_at'),
                        'deleted_at' => $product && $product->trashed()
                            ? $this->productEquipaments->getFormattedDate($product, 'deleted_at')
                            : $product->deleted_at ?? null,
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
        DB::beginTransaction();
        try {
            $user = $request->user();
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id');

            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $validatedData = $request->validate(
                $this->productEquipaments->rulesProductEquipamentos(),
                $this->productEquipaments->feedbackProductEquipaments()
            );

            // Verificar se o produto é um grupo
            if ($validatedData['is_grup'] == 0) {
                // Criar um produto individual
                $createProductEquipaments = $this->productEquipaments->create([
                    'name' => $request->name,
                    'quantity_min' => $request->quantity_min,
                    'fk_category_id' => $request->fk_category_id,
                    'observation' => $request->observation,
                    'expiration_date' => $request->expiration_date,
                    'is_grup' => $request->is_grup,
                ]);
            } else {
                // Criar um produto do tipo grupo
                $createProductEquipaments = $this->productEquipaments->create([
                    'name' => $validatedData['name'],
                    'quantity_min' => null, // Grupos não têm quantidade mínima
                    'fk_category_id' => $validatedData['fk_category_id'],
                    'observation' => $validatedData['observation'],
                    'expiration_date' => $validatedData['expiration_date'],
                    'is_grup' => $validatedData['is_grup'],
                ]);

                // Relacionar os produtos ao grupo
                if (!empty($validatedData['list_products'])) {
                    foreach ($validatedData['list_products'] as $componentId) {
                        DB::table('product_groups')->insert([
                            'group_product_id' => $createProductEquipaments->id,
                            'component_product_id' => $componentId,
                        ]);
                    }
                }
            }

            if ($createProductEquipaments) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'products_equipaments',
                    'record_id' => $createProductEquipaments->id,
                    'description' => 'Adicionou um produto.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Produto cadastrado com sucesso.",
                    'data' => $createProductEquipaments,
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

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id');

            if ($user->level !== 'admin' && $user->level !== 'manager' && $categoryUser == null) {
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

            $originalData = $updateProductEquipaments->getOriginal();

            $validatedData = $request->validate(
                $this->productEquipaments->rulesProductEquipamentos(),
                $this->productEquipaments->feedbackProductEquipaments(),
            );

            $updateProductEquipaments->fill($validatedData);
            $updateProductEquipaments->save();

            // Verificando as mudanças e criando a string de log
            $changes = $updateProductEquipaments->getChanges(); // Retorna apenas os campos que foram alterados
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
                'table_name' => 'products_equipaments',
                'record_id' => $id,
                'description' => 'Atualizou um produto. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

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
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user' || $level == 'manager') {
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

            $deleted = $deleteProductEquipaments->delete();

            if ($deleted) {
                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'product_equipaments',
                    'record_id' => $id,
                    'description' => 'Excluiu um produto.',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Produto/Equipamento removido com sucesso.',
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

    public function reverseDeletedProduct(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $product = ProductEquipament::withTrashed()->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }
            if ($product->deleted_at == false) {

                return response()->json([
                    'success' => true,
                    'message' => 'Não foi possível executar essa ação, produto não pertence aos deletados.',
                ]);
            }

            $product->restore();

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Restaurou',
                'table_name' => 'product_equipaments',
                'record_id' => $id,
                'description' => 'Retornou um produto deletado aos ativos.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produto retornou aos ativos.',
                'data' => $product
            ]);
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
}