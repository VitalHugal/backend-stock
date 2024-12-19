<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAlert;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PDFBuyProductsOnAlertController extends Controller
{
    public function generatedPDFBuyProductOnAlert(Request $request)
    {
        try {
            DB::beginTransaction();

            // verifica se o id informado na requisição existe, senão retorna erro
            $product_alert = ProductAlert::all();

            // nome do projeto
            $userName = $request->user('name');

            // data atual da maquina;
            $date = date('d-m-Y H:i:s');

            // briefing do projeto
            $briefing = $product_alert->briefing;

            // Tempo total do projeto em horas e minutos
            $timeTotal = $product_alert->tempo;
            $hours = intdiv($timeTotal, 60);
            $minutes = $timeTotal % 60;

            ////////////////////////////////////////////////////////////////////////////////////////////
            // observações do projeto
            $observations = $product_alert->observacoes;

            if ($observations === null || $observations === "[]") {
                $observationsFinal = "";
            } else {
                $observationsArray = json_decode($observations, true);

                // Acessar os itens individualmente
                foreach ($observationsArray as $itemObs) {
                    $arrayObsResult[] = "<div id='obsResult'>" . '<li id="list-observations">' . $itemObs . '</li>' . "</div>" . PHP_EOL;
                }

                $observationsFinal = implode(" ", $arrayObsResult);
            }
            ////////////////////////////////////////////////////////////////////////////////////////////

            ////////////////////////////////////////////////////////////////////////////////////////////
            // grupos e tempo total de cada grupo
            $groups = json_decode($product_alert->registros);

            // inicializando a variavel
            $mergedGroups = [];

            if ($groups !== null) {
                // loop para ercuperar o apenas o nome eo tempo de cada grupo
                foreach ($groups as $groupsSingle) {
                    if (isset($groupsSingle->name, $groupsSingle->time)) {
                        $mergedGroups[] = [
                            'name' => $groupsSingle->name,
                            'time' => $groupsSingle->time,
                        ];
                    }
                }
            }
            // inicializando a variavel
            $mergedGroupsString = [];

            // loop para pegar nome do grupoe tempo formatado e separado
            foreach ($mergedGroups as $item) {
                $mergedGroupsString[] = '<div id="groups">' . '<span id="time-groups-name">' . $item['name'] . '</span>' . '<span id="time-groups">' . intdiv($item['time'], 60) . 'H:' . ($item['time'] % 60) . 'm' . '</span>' . '<br>' . "</div>";
            }
            // transformando em string
            $str = implode(' ', $mergedGroupsString);
            ////////////////////////////////////////////////////////////////////////////////////////////

            // Valores recebidos no html do PDF 
            $data = [
                // 'name' => $userName,
                'date' => $date,
                'briefing' => $briefing,
                'hours' => $hours,
                'minutes' => $minutes,
                'groups' => $str,
                'observations' => $observationsFinal,
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
            
            DB::commit();
            
            // hack para liberar no front a requisição (HACK DE CORS) - IMPORTANTE !!!
            header('Access-Control-Allow-Origin: *');

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