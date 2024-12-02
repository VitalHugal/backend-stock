<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemLogsController extends Controller
{
    public function getAllLogs(Request $request)
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

            $getAllSystemLogs = DB::table('system_logs')->get();

            dd();

            if ($getAllSystemLogs) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $getAllSystemLogs,
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