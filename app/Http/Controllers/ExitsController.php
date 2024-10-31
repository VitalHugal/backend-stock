<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use App\Models\ProductEquipament;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
            $user = $request->user();

            $productEquipament = ProductEquipament::where('id', $id)->first();

            $quantityProductEquipament = $productEquipament->quantity;

            $validateData = $request->validate(
                $this->exits->rulesExits(),
                $this->exits->feedbackExits()
            );

            $fk_product_equipament_id = $request->fk_product_equipament_id;
            $fk_user_id = $request->fk_user_id;
            $reason_project = $request->reason_project;
            $observation = $request->observation;
            $quantity = $request->quantity;
            $withdrawal_date = $request->withdrawal_date;
            $withdrawal_name_user = $request->withdrawal_name_user;

            if ($quantity > $quantityProductEquipament) {
                return response()->json([
                   'success' => false, 
                   'message' => 'Quantidade insuficiente em estoque. Temos apenas. $quantityProductEquipament .unidades disponíveis.', 
                ]);
            }

            if ($validateData) {
                $exits = $this->exits->create([
                    'fk_product_equipament_id' => $fk_product_equipament_id,
                    'fk_user_id' => $fk_user_id,
                    'reason_project' => $reason_project,
                    'observation' => $observation,
                    'quantity' => $quantity,
                    'withdrawal_date' => $withdrawal_date,
                    'withdrawal_name_user' => $withdrawal_name_user,
                ]);
            }

            if ($exits) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retirada concluida com sucesso',
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
}