<?php

namespace App\Http\Controllers;

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

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            //SEARCH DATE, NAME E ACTION

            if ($request->has('date') && $request->input('date') != '' && $request->has('name') && $request->input('name') != '' && $request->has('action') && $request->input('action') != '') {

                $search = $request->input('date');
                $explodedSearch = explode("/", $search);
                $searchFormatted = $explodedSearch[2] . '-' . $explodedSearch[1] . '-' . $explodedSearch[0];

                $logsSearch = SystemLog::with('user')
                    ->when($request->has('name') && $request->input('name') !== '', function ($query) use ($request) {
                        $query->whereHas('user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'like', '%' . $request->input('name') . '%');
                        });
                    })
                    ->when($request->has('action') && $request->input('action') !== '', function ($query) use ($request) {
                        $query->where('action', 'like', '%' . $request->input('action') . '%');
                    })
                    ->when($request->has('date') && $request->input('date') !== '', function ($query) use ($request, $searchFormatted) {
                        $query->where('created_at', 'like', '%' . $searchFormatted . '%');
                    })
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends($request->only(['name', 'action', 'date']));

                $logsSearch->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearch,
                ]);
            }

            //SEARCH DATE E ACTION
            if ($request->has('date') && $request->input('date') != '' && $request->has('action') && $request->input('action') != '') {

                $search = $request->input('date');
                $explodedSearch = explode("/", $search);
                $searchFormatted = $explodedSearch[2] . '-' . $explodedSearch[1] . '-' . $explodedSearch[0];

                $logsSearch = SystemLog::with('user')
                    ->where('created_at', 'like', '%' . $searchFormatted . '%')
                    ->where('action', 'like', '%' . $request->input('action') . '%')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends(['date' => $search, 'action' => $request->input('action')]);

                $logsSearch->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearch,
                ]);
            }

            //SEARCH DATE E NAME
            if ($request->has('date') && $request->input('date') != '' && $request->has('name') && $request->input('name') != '') {

                $search = $request->input('date');
                $explodedSearch = explode("/", $search);
                $searchFormatted = $explodedSearch[2] . '-' . $explodedSearch[1] . '-' . $explodedSearch[0];

                $logsSearch = SystemLog::with('user')
                    ->when($request->has('name') && $request->input('name') !== '', function ($query) use ($request) {
                        $query->whereHas('user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'like', '%' . $request->input('name') . '%');
                        });
                    })
                    ->when($request->has('date') && $request->input('date') !== '', function ($query) use ($request, $searchFormatted) {
                        $query->where('created_at', 'like', '%' . $searchFormatted . '%');
                    })
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends($request->only(['name', 'action', 'date']));

                $logsSearch->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearch,
                ]);
            }

            //SEARCH ACTION E NAME
            if ($request->has('action') && $request->input('action') != '' && $request->has('name') && $request->input('name') != '') {

                $logsSearch = SystemLog::with('user')
                    ->when($request->has('name') && $request->input('name') !== '', function ($query) use ($request) {
                        $query->whereHas('user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'like', '%' . $request->input('name') . '%');
                        });
                    })
                    ->when($request->has('action') && $request->input('action') !== '', function ($query) use ($request) {
                        $query->where('action', 'like', '%' . $request->input('action') . '%');
                    })
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends($request->only(['name', 'action']));

                $logsSearch->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearch,
                ]);
            }


            //SEARCH DATE
            if ($request->has('date') && $request->input('date') != '') {

                $search = $request->input('date');
                $explodedSearch = explode("/", $search);
                $searchFormatted = $explodedSearch[2] . '-' . $explodedSearch[1] . '-' . $explodedSearch[0];

                $logsSearchDate = SystemLog::with('user')
                    ->where('created_at', 'like', '%' . $searchFormatted . '%')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends(['date' => $search]);

                $logsSearchDate->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearchDate->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearchDate,
                ]);
            }

            //SEARCH NAME
            if ($request->has('name') && $request->input('name') != '') {

                $logsSearchName = SystemLog::select('system_logs.*')
                    ->join('users', 'users.id', '=', 'system_logs.fk_user_id')
                    ->where('users.name', 'like', '%' . $request->input('name') . '%')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends(['name' => $request->input('name')]);

                $logsSearchName->getCollection()->transform(function ($logs) {

                    $updated_at = 'updated_at';
                    $created_at = 'created_at';

                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, $created_at),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, $updated_at),
                    ];
                });


                if ($logsSearchName->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearchName,
                ]);
            }

            //SEARCH ACTION
            if ($request->has('action') && $request->input('action') != '') {

                $logsSearch = SystemLog::with('user')
                    ->where('action', 'like', '%' . $request->input('action') . '%')
                    ->orderBy('id', 'desc')
                    ->paginate(10)
                    ->appends(['action' => $request->input('action')]);

                $logsSearch->getCollection()->transform(function ($logs) {
                    return [
                        'id' => $logs->id,
                        'fk_user_id' => $logs->fk_user_id ?? null,
                        'name_user' => $logs->user->trashed()
                            ? $logs->user->name . ' (Deletado)'
                            : $logs->user->name ?? null,
                        'action' => $logs->action,
                        'table_name' => $logs->table_name,
                        'record_id' => $logs->record_id,
                        'description' => $logs->description,
                        'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                        'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
                    ];
                });

                if ($logsSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para a busca solicitada.',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Logs recuperados com sucesso.',
                    'data' => $logsSearch,
                ]);
            }

            //GET ALL

            $getAllLogs = SystemLog::with('user')
                ->orderBy('id', 'desc')
                ->paginate(10);

            $getAllLogs->getCollection()->transform(function ($logs) {

                return [
                    'id' => $logs->id,
                    'fk_user_id' => $logs->fk_user_id ?? null,
                    'name_user' => $logs->user->trashed()
                        ? $logs->user->name . ' (Deletado)'
                        : $logs->user->name ?? null,
                    'action' => $logs->action,
                    'table_name' => $logs->table_name,
                    'record_id' => $logs->record_id,
                    'description' => $logs->description,
                    'created_at' => $this->system_logs->getFormattedDate($logs, 'created_at'),
                    'updated_at' => $this->system_logs->getFormattedDate($logs, 'updated_at'),
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