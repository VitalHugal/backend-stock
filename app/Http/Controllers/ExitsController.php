<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\ProductEquipament;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExitsController extends CrudController
{
    protected $exits;

    public function __construct(Exits $exits)
    {
        parent::__construct($exits);

        $this->exits = $exits;
    }

    public function exits(Request $request, $id)
    {
        try {
            $productEquipament = ProductEquipament::where('id', $id)->first();

            if (!$productEquipament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto/equipamento encontrado.',
                ]);
            }

            $date = now();
            $quantityProductEquipament = $productEquipament->quantity;

            $numQuantity = intval($request->quantity);

            if ($numQuantity > $quantityProductEquipament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade insuficiente em estoque. Temos apenas ' . $quantityProductEquipament . ' unidades disponíveis.',
                ]);
            }

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

            $newQuantityProductEquipament = $quantityProductEquipament - $numQuantity;

            if ($request->fk_product_equipament_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Divergência na identifição do produto/equipamento.',
                ]);
            }

            $exits = Exits::create([
                'fk_product_equipament_id' => $id,
                'fk_user_id' => $request->fk_user_id,
                'reason_project' => $request->reason_project,
                'observation' => $request->observation,
                'quantity' => $numQuantity,
                'withdrawal_date' => $date,
                'delivery_to' => $request->delivery_to,
            ]);

            if ($exits) {
                ProductEquipament::where('id', $id)->update(['quantity' => $newQuantityProductEquipament]);

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

            $quantityReturnStock = $deleteExits->quantity;
            $idProduct = $deleteExits->fk_product_equipament_id;

            if (!$deleteExits) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteExits->delete();

            if ($deleteExits) {
                ProductEquipament::where('id', $idProduct)->update(['quantity' => $quantityReturnStock]);

                return response()->json([
                    'success' => true,
                    'message' => 'Usuário removido com sucesso.',
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