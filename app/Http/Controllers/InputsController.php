<?php

namespace App\Http\Controllers;

use App\Models\Inputs;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class InputsController extends CrudController
{
    protected $input;

    public function __construct(Inputs $inputs)
    {
        parent::__construct($inputs);

        $this->input = $inputs;
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validatedData = $request->validate(
                $this->input->rulesInputs(),
                $this->input->feedbackInputs()
            );

            if ($validatedData) {
                $input = $this->input->create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'quantity' => $request->quantity,
                    'fk_user_id' => $user->id,
                ]);
            }

            if ($input) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada criada com sucesso.',
                    'data' => $input,
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

    public function update(Request $request, $id)
    {
        try {

            $updateInput = $this->input->find($id);

            $user = $request->user();

            if (!$updateInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

            $validatedData = [
                'fk_product_equipament_id' => $request->fk_product_equipament_id,
                'quantity' => $request->quantity,
            ];

            $validatedData = $request->validate(
                $this->input->rulesInputs(),
                $this->input->feedbackInputs()
            );

            $updateInput->fill($validatedData);
            $updateInput->save();

            if ($updateInput->save()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada atualizada com sucesso.',
                    'data' => $updateInput,
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