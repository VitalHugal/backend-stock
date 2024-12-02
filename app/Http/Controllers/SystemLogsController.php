<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SystemLogsController extends CrudController
{

    protected $system_logs;

    public function __construct(SystemLog $system_logs)
    {
        parent::__construct($system_logs); {
            $this->system_logs = $system_logs;
        }
    }

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

            if ($request->has('name') && $request->input('name') != '') {

                $logsSearch = SystemLog::select('system_logs.*')
                    ->join('users', 'users.id', '=', 'system_logs.fk_user_id')
                    ->where('users.name', 'like', '%' . $request->input('name') . '%')
                    ->paginate(10)
                    ->appends(['name' => $request->input('name')]);

                $logsSearch->getCollection()->transform(function ($logs) {

                    $updated_at = 'updated_at';
                    $created_at = 'created_at';

                    return [
                        'id' => $logs->id,
                        'name-user' => $logs->user->name,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, $created_at),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, $updated_at),
                    ];
                });


                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum produto encontrado com o nome informado.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso (pesquisa).',
                    'data' => $logsSearch,
                ]);
            }

            $getAllLogs = SystemLog::with('user')
                ->orderBy('id', 'desc')
                ->paginate(10);

            $getAllLogs->getCollection()->transform(function ($logs) {

                $updated_at = 'updated_at';
                $created_at = 'created_at';

                return [
                    'id' => $logs->id,
                    'name-user' => $logs->user->name,
                    'action' => $logs->action,
                    'table_name' => $logs->table_name,
                    'record_id' => $logs->record_id,
                    'description' => $logs->description,
                    'created_at' => $this->system_logs->getFormattedDate($logs, $created_at),
                    'updated_at' => $this->system_logs->getFormattedDate($logs, $updated_at),
                ];
            });

            if ($getAllLogs) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $getAllLogs,
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