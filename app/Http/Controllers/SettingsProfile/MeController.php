<?php

namespace App\Http\Controllers\SettingsProfile;

use App\Http\Controllers\CrudController;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MeController extends CrudController
{
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dados recuperados com sucesso.',
                    'data' => $user,
                ]);
            }
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ]);
        }
    }
}