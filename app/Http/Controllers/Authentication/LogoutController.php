<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $idUser = $user->id;
            $user->tokens()->delete();

            if ($user) {
                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'logout',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Usuário desconectado com sucesso.'
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
}