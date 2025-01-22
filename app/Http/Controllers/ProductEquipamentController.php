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
use Symfony\Component\Console\Input\Input;

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

                if ($request->has('active') && $request->input('active') != '') {

                    if ($request->input('active') == 'true') {
                        $productEquipamentUserSearch = ProductEquipament::with(['category' => function ($query) {
                            $query->whereNull('deleted_at');
                        }])
                            ->whereHas('category', function ($query) {
                                $query->whereNull('deleted_at');
                            })

                            ->whereIn('fk_category_id', $categoryUser)
                            ->when($request->has('is_group') && in_array($request->input('is_group'), ['0', '1']), function ($query) use ($request) {
                                $query->where('is_group', $request->input('is_group'));
                            })
                            ->when($request->has('name') && ($request->input('name') != ''), function ($query) use ($request) {
                                $query->where('name', 'like', '%' . $request->input('name') . '%');
                            })
                            ->orderBy('fk_category_id', 'asc')
                            ->paginate(10)
                            ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);
                    }

                    if ($request->input('active') == 'false') {
                        $productEquipamentUserSearch = ProductEquipament::onlyTrashed(['category' => function ($query) {
                            $query->whereNull('deleted_at');
                        }])
                            ->whereHas('category', function ($query) {
                                $query->whereNull('deleted_at');
                            })

                            ->whereIn('fk_category_id', $categoryUser)
                            ->when($request->has('is_group') && in_array($request->input('is_group'), ['0', '1']), function ($query) use ($request) {
                                $query->where('is_group', $request->input('is_group'));
                            })
                            ->when($request->has('name') && ($request->input('name') != ''), function ($query) use ($request) {
                                $query->where('name', 'like', '%' . $request->input('name') . '%');
                            })
                            ->orderBy('fk_category_id', 'asc')
                            ->paginate(10)
                            ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);
                    }

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

                        $componentsGroup = $product->is_group == 1
                            ? DB::table('product_groups')
                            ->where('group_product_id', $product->id)
                            ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                            ->select('products_equipaments.id', 'products_equipaments.name')
                            ->get()
                            : [];

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
                            'components_group' => $componentsGroup,
                            'quantity_min' => $product->quantity_min,
                            'is_group' => $product->is_group,
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
                        ->where('is_group', 0)
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

                        $componentsGroup = $product->is_group == 1
                            ? DB::table('product_groups')
                            ->where('group_product_id', $product->id)
                            ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                            ->select('products_equipaments.id', 'products_equipaments.name')
                            ->get()
                            : [];

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
                            'components_group' => $componentsGroup,
                            'quantity_min' => $product->quantity_min,
                            'is_group' => $product->is_group,
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

                        $componentsGroup = $product->is_group == 1
                            ? DB::table('product_groups')
                            ->where('group_product_id', $product->id)
                            ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                            ->select('products_equipaments.id', 'products_equipaments.name')
                            ->get()
                            : [];

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
                                'is_group' => $product->is_group,
                                'components_group' => $componentsGroup,
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

                    $componentsGroup = $product->is_group == 1
                        ? DB::table('product_groups')
                        ->where('group_product_id', $product->id)
                        ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                        ->select('products_equipaments.id', 'products_equipaments.name')
                        ->get()
                        : [];

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
                        'is_group' => $product->is_group,
                        'components_group' => $componentsGroup,
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
                    ->where('is_group', 0)
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

                    $componentsGroup = $product->is_group == 1
                        ? DB::table('product_groups')
                        ->where('group_product_id', $product->id)
                        ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                        ->select('products_equipaments.id', 'products_equipaments.name')
                        ->get()
                        : [];

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
                        'components_group' => $componentsGroup,
                        'quantity_min' => $product->quantity_min,
                        'is_group' => $product->is_group,
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

            //filtro com nome
            if ($request->has('active') &&  $request->input('active') != '') {

                if ($request->input('active') == 'true') {

                    $productAllAdminSearch = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })

                        // ->whereIn('fk_category_id', $categoryUser)
                        ->when($request->has('is_group') && in_array($request->input('is_group'), ['0', '1']), function ($query) use ($request) {
                            $query->where('is_group', $request->input('is_group'));
                        })
                        ->when($request->has('name') && ($request->input('name') != ''), function ($query) use ($request) {
                            $query->where('name', 'like', '%' . $request->input('name') . '%');
                        })
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);
                }

                if ($request->input('active') == 'false') {
                    $productAllAdminSearch = ProductEquipament::onlyTrashed(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) {
                            $query->whereNull('deleted_at');
                        })

                        ->when($request->has('is_group') && in_array($request->input('is_group'), ['0', '1']), function ($query) use ($request) {
                            $query->where('is_group', $request->input('is_group'));
                        })
                        ->when($request->has('name') && ($request->input('name') != ''), function ($query) use ($request) {
                            $query->where('name', 'like', '%' . $request->input('name') . '%');
                        })
                        ->orderBy('fk_category_id', 'asc')
                        ->paginate(10)
                        ->appends(['name' => $request->input('name'), 'active' => $request->input('active')]);
                }

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

                    $componentsGroup = $product->is_group == 1
                        ? DB::table('product_groups')
                        ->where('group_product_id', $product->id)
                        ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                        ->select('products_equipaments.id', 'products_equipaments.name')
                        ->get()
                        : [];

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
                        'is_group' => $product->is_group,
                        'components_group' => $componentsGroup,
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

                    $componentsGroup = $product->is_group == 1
                        ? DB::table('product_groups')
                        ->where('group_product_id', $product->id)
                        ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                        ->select('products_equipaments.id', 'products_equipaments.name')
                        ->get()
                        : [];

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
                            'is_group' => $product->is_group,
                            'components_group' => $componentsGroup,
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

                $componentsGroup = $product->is_group == 1
                    ? DB::table('product_groups')
                    ->where('group_product_id', $product->id)
                    ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                    ->select('products_equipaments.id', 'products_equipaments.name')
                    ->get()
                    : [];

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
                    'is_group' => $product->is_group,
                    'components_group' => $componentsGroup,
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

                        $componentsGroup = $product->is_group == 1
                            ? DB::table('product_groups')
                            ->where('group_product_id', $product->id)
                            ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                            ->select('products_equipaments.id', 'products_equipaments.name')
                            ->get()
                            : [];

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
                            'is_group' => $product->is_group,
                            'components_group' => $componentsGroup,
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

                    $componentsGroup = $product->is_group == 1
                        ? DB::table('product_groups')
                        ->where('group_product_id', $product->id)
                        ->join('products_equipaments', 'product_groups.component_product_id', '=', 'products_equipaments.id')
                        ->select('products_equipaments.id', 'products_equipaments.name')
                        ->get()
                        : [];

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
                        'is_group' => $product->is_group,
                        'components_group' => $componentsGroup,
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

            // Validações
            $validatedData = $request->validate(
                $this->productEquipaments->rulesProductEquipaments(),
                $this->productEquipaments->feedbackProductEquipaments()
            );

            $validatedDataIsGrup = $request->validate(
                $this->productEquipaments->rulesProductEquipamentsIsGrup(),
                $this->productEquipaments->feedbackProductEquipamentsIsGrup()
            );

            $listProducts = $validatedDataIsGrup['list_products'] ?? [];
            $createdProduct = null;

            if ($request->is_group == 0) {
                $createdProduct = $this->productEquipaments->create([
                    'name' => $validatedData['name'],
                    'quantity_min' => $validatedData['quantity_min'],
                    'fk_category_id' => $validatedData['fk_category_id'],
                    'observation' => $validatedData['observation'],
                    'expiration_date' => $validatedData['expiration_date'],
                    'is_group' => $validatedData['is_group'],
                ]);
            } else {
                $createdProduct = $this->productEquipaments->create([
                    'name' => $validatedData['name'],
                    'quantity_min' => $validatedDataIsGrup['quantity_min'],
                    'fk_category_id' => $validatedData['fk_category_id'],
                    'observation' => $request->observation,
                    'expiration_date' => 0,
                    'is_group' => $validatedData['is_group'],
                    'list_products' => $listProducts,
                ]);

                foreach ($listProducts as $componentId) {
                    DB::table('product_groups')->insert([
                        'group_product_id' => $createdProduct->id,
                        'component_product_id' => $componentId,
                    ]);
                }
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Adicionou',
                'table_name' => 'products_equipaments',
                'record_id' => $createdProduct->id,
                'description' => 'Adicionou um produto.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Produto cadastrado com sucesso.",
                'data' => $createdProduct,
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

            if ($updateProductEquipaments->is_group == '1') {

                $validatedData = $request->validate(
                    $this->productEquipaments->rulesProductEquipamentsIsGrup(),
                    $this->productEquipaments->feedbackProductEquipamentsIsGrup(),
                );

                $currentProducts = DB::table('product_groups')
                    ->where('group_product_id', $id)
                    ->pluck('component_product_id')
                    ->toArray();

                $newProductIds = $request->input('list_products', []);

                if ($currentProducts !== $newProductIds) {

                    // Identifica produtos para remover
                    $productsToRemove = array_diff($currentProducts, $newProductIds);

                    // Identifica produtos para adicionar
                    $productsToAdd = array_diff($newProductIds, $currentProducts);

                    // Remove produtos que não estão mais no novo array
                    if (!empty($productsToRemove)) {
                        DB::table('product_groups')
                            ->where('group_product_id', $id)
                            ->whereIn('component_product_id', $productsToRemove)
                            ->delete();
                    }

                    // Adiciona novos produtos
                    if (!empty($productsToAdd)) {
                        $dataToInsert = [];
                        foreach ($productsToAdd as $productId) {
                            $dataToInsert[] = [
                                'group_product_id' => $id,
                                'component_product_id' => $productId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('product_groups')->insert($dataToInsert);
                    }

                    // $updateProductEquipaments->fill($validatedData);
                    // $updateProductEquipaments->save();
                }
            } else {
                $validatedData = $request->validate(
                    $this->productEquipaments->rulesProductEquipaments(),
                    $this->productEquipaments->feedbackProductEquipaments(),
                );
            }

            $updateProductEquipaments->fill($validatedData);
            $updateProductEquipaments->save();

            $changes = $updateProductEquipaments->getChanges();
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