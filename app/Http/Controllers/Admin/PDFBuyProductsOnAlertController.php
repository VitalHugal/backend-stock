<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Models\Category;
use App\Models\Exits;
use App\Models\Inputs;
use App\Models\ProductAlert;
use App\Models\ProductEquipament;
use App\Models\Reservation;
use App\Models\SystemLog;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PDFBuyProductsOnAlertController extends CrudController
{
    protected $product_alert;

    public function __construct(ProductAlert $product_alert)
    {
        parent::__construct($product_alert);

        $this->product_alert = $product_alert;
    }

    public function generatedPDFBuyProductOnAlert(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $userName = $user->name;
            // $userName = '$user->name';

            if ($user->level == 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.'
                ]);
            }

            if ($user->level == 'admin' || $user->level == 'manager') {

                if ($request->has('category') && $request->input('category') != '') {
                    $categoryList = Category::listCategorys($request);

                    if ($categoryList->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nenhum resultado encontrado.',
                        ]);
                    }

                    $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                        ->whereHas('category', function ($query) use ($categoryList) {
                            $query->whereIn('id', $categoryList->original->pluck('id'));
                        })
                        ->orderBy('fk_category_id', 'asc')
                        ->get();
                } else {

                    $productAllAdmin = ProductEquipament::with(['category' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])->whereHas('category', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                        ->orderBy('fk_category_id', 'asc')
                        ->get();

                    // $namesCategorysNoResultFormatted = ''; 
                }

                $filteredCollectionAdmin = $productAllAdmin->filter(function ($product) {
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

                    // Retorna somente os produtos que atendem à condição
                    return $quantityTotalProduct <= $product->quantity_min;
                })->map(function ($product) {
                    $productEquipamentId = $product->id;

                    // Recalcula as quantidades totais
                    $quantityTotalInputs = Inputs::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityTotalExits = Exits::where('fk_product_equipament_id', $productEquipamentId)->sum('quantity');
                    $quantityReserveNotFinished = Reservation::where('fk_product_equipament_id', $productEquipamentId)
                        ->where('reservation_finished', false)
                        ->whereNull('date_finished')
                        ->whereNull('fk_user_id_finished')
                        ->sum('quantity');

                    $quantityTotalProduct = $quantityTotalInputs - ($quantityTotalExits + $quantityReserveNotFinished);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity_stock' => $quantityTotalProduct,
                        'quantity_min' => $product->quantity_min,
                        'name-category' => $product->category->name ?? null,
                    ];
                });
            }

            // data atual da maquina;
            $date = date('d-m-Y H:i:s');

            $itemsFirstPage = 22;
            $itemsPerPage = 26;

            // Divide os itens para a primeira página e as demais
            $firstPageItems = $filteredCollectionAdmin->slice(0, $itemsFirstPage)->toArray();
            $remainingItems = $filteredCollectionAdmin->slice($itemsFirstPage)->toArray();

            // Cria um array de páginas
            $productChunks = array_merge(
                [$firstPageItems],
                array_chunk($remainingItems, $itemsPerPage)
            );

            $data = [
                'name' => $userName,
                'date' => $date,
                'pages' => $productChunks,
                // 'notFound' => $namesCategorysNoResultFormatted,
            ];

            // Carregar conteúdo HTML
            $html = view('pdf.document', $data)->render();

            // Instanciar Dompdf
            $dompdf = new Dompdf();

            $dompdf->loadHtml($html);

            // Selecionar papel e tamanhos
            $dompdf->setPaper('A4', 'portrait');

            // Renderizar PDF
            $dompdf->render();

            // hack para liberar no front a requisição (HACK DE CORS) - IMPORTANTE !!!
            header('Access-Control-Allow-Origin: *');

            DB::commit();

            // Saida do PDF no naveagdor
            return $dompdf->stream('document.pdf');
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