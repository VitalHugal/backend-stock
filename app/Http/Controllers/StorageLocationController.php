<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageLocationController extends CrudController
{
    protected $storage_location;

    public function __construct(StorageLocation $storage_location)
    {
        parent::__construct($storage_location); {
            $this->storage_location = $storage_location;
        }
    }

    public function getAllStorageLocation(Request $request)
    {
        try {
            $user = $request->user();
            $level = $user->level;

            // if ($level == 'user') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Você não tem permissão de acesso para seguir adiante.',
            //     ]);
            // }

            if ($request->has('active') && $request->input('active') != '' && $request->has('name') && $request->input('name') != '') {

                if ($request->input('active') == 'true') {
                    $getAllStorageLocationSearch = StorageLocation::where('name', 'like', '%' . $request->input('name') . '%')
                        ->paginate(10)
                        ->appends(['active' => $request->input('active'), 'name' => $request->input('name')]);
                }

                if ($request->input('active') == 'false') {
                    $getAllStorageLocationSearch = StorageLocation::onlyTrashed()
                        ->where('name', 'like', '%' . $request->input('name') . '%')
                        ->paginate(10)
                        ->appends(['active' => $request->input('active'), 'name' => $request->input('name')]);
                }

                if ($getAllStorageLocationSearch->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum resultado encontrado para pesquisa solicitada.',
                    ]);
                }

                $getAllStorageLocationSearch->getCollection()->transform(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->trashed()
                            ? $location->name . ' (Deletado)'
                            : $location->name ?? null,
                        'observation' => $location->observation,
                        'created_at' => $this->storage_location->getFormattedDate($location, 'created_at'),
                        'updated_at' => $this->storage_location->getFormattedDate($location, 'updated_at'),
                        'deleted_at' => $location->deleted_at
                            ? $this->storage_location->getFormattedDate($location, 'deleted_at')
                            : null,
                    ];
                });

                if ($getAllStorageLocationSearch) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Locais de armazenamento recuperados com sucesso para pesquisa solicitada.',
                        'data' => $getAllStorageLocationSearch,
                    ]);
                }
            }

            if ($request->has('active') && $request->input('active') == 'true') {
                $getAllStorageLocation = StorageLocation::paginate(10)
                    ->appends(['active' => 'true']);
            } elseif ($request->has('active') && $request->input('active') == 'false') {
                $getAllStorageLocation = StorageLocation::withTrashed()
                    ->whereNotNull('deleted_at')
                    ->paginate(10)
                    ->appends(['active' => 'false']);
            } else {
                $getAllStorageLocation = StorageLocation::withTrashed()->paginate(10);
            }

            $getAllStorageLocation->getCollection()->transform(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->trashed()
                        ? $location->name . ' (Deletado)'
                        : $location->name ?? null,
                    'observation' => $location->observation,
                    'created_at' => $this->storage_location->getFormattedDate($location, 'created_at'),
                    'updated_at' => $this->storage_location->getFormattedDate($location, 'updated_at'),
                    'deleted_at' => $location->deleted_at
                        ? $this->storage_location->getFormattedDate($location, 'deleted_at')
                        : null,
                ];
            });

            if ($getAllStorageLocation) {
                return response()->json([
                    'success' => true,
                    'message' => 'Locais de armazenamento recuperados com sucesso.',
                    'data' => $getAllStorageLocation,
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

    public function getIdStorageLocation(Request $request, $id)
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

            $getIdStorageLocation = StorageLocation::where('id', $id)->get();

            if ($getIdStorageLocation->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $getIdStorageLocation = $getIdStorageLocation->transform(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->trashed()
                        ? $location->name . ' (Deletado)'
                        : $location->name ?? null,
                    'observation' => $location->observation,
                    'created_at' => $this->storage_location->getFormattedDate($location, 'created_at'),
                    'updated_at' => $this->storage_location->getFormattedDate($location, 'updated_at'),
                    'deleted_at' => $location->deleted_at
                        ? $this->storage_location->getFormattedDate($location, 'deleted_at')
                        : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Local de armazenamento recuperado com sucesso.',
                'data' => $getIdStorageLocation
            ]);
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

    public function storageLocation(Request $request)
    {
        DB::beginTransaction();

        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }
            $validatedData = $request->validate(
                $this->storage_location->rules(),
                $this->storage_location->feedback(),
            );

            $name = $request->name;
            $observation = $request->observation;

            $createStorageLocation = $validatedData;

            $storageLocationExist = StorageLocation::where('name', $name)->first();

            if ($storageLocationExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Um local de armazenamento com esse nome já existe.',
                ]);
            }

            $createStorageLocation = $this->storage_location->create([
                'name' => $name,
                'observation' => $observation,
            ]);

            if ($createStorageLocation) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'stoarge_location',
                    'record_id' => $createStorageLocation->id,
                    'description' => 'Adicionou um novo local de armazenamento.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Local de armazenamento criado com sucesso.',
                    'data' => $createStorageLocation,
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

    public function updateStorageLocation(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $updateStorageLocation = StorageLocation::where('id', $id)->first();

            if (!$updateStorageLocation) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $originalData = $updateStorageLocation->getOriginal();

            $validateData = $request->validate(
                $this->storage_location->rules(),
                $this->storage_location->feedback()
            );

            $updateStorageLocation->fill($validateData);
            $updateStorageLocation->save();

            // Verificando as mudanças e criando a string de log
            $changes = $updateStorageLocation->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} #";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'storage_location',
                'record_id' => $id,
                'description' => 'Atualizou um local de armazenamento. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Local de armazenamento atualizado com sucesso.',
                'data' => $updateStorageLocation,
            ]);
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

    public function delete(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteStorageLocation = $this->storage_location->find($id);

            if (!$deleteStorageLocation) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteStorageLocation->delete();

            if ($deleteStorageLocation) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'storage_location',
                    'record_id' => $id,
                    'description' => 'Excluiu um local de armazenamento.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Local de armazenamento removido com sucesso.',
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

    public function reverseDeletedStorageLocation(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }
            $storage_locations = StorageLocation::withTrashed()->find($id);

            if (!$storage_locations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            if ($storage_locations->deleted_at == false) {

                return response()->json([
                    'success' => true,
                    'message' => 'Não foi possível executar essa ação, setor não pertence aos deletados.',
                ]);
            }

            $storage_locations->restore();

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Restaurou',
                'table_name' => 'storage_locations',
                'record_id' => $id,
                'description' => 'Retornou setor deletado aos ativos.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Local de armazenamento retornou aos ativos.',
                'data' => $storage_locations
            ]);
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