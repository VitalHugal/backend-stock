<?php

namespace App\Http\Controllers;

use App\Models\Exits;
use App\Models\Inputs;
use App\Models\SystemLog;
use App\Services\InputService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\Input;

class InputsController extends CrudController
{
    protected $input;
    protected $input_service;

    public function __construct(Inputs $inputs, InputService $inputService)
    {
        parent::__construct($inputs, $inputService);

        $this->input = $inputs;
        $this->input_service = $inputService;
    }

    public function getAllInputs(Request $request)
    {
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && $user->level !== 'manager' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            // Verifica o nível de acesso e filtra as entradas
            if ($level == 'user') {

                $inputs = Inputs::with(['productEquipament.category' => function ($query) {
                    $query->withTrashed();
                }, 'user' => function ($query) {
                    $query->withTrashed();
                }, 'storage_location' => function ($query) {
                    $query->withTrashed();
                }])
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser)
                            ->withTrashed();
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                if ($request->has('product') && $request->input('product') != '') {

                    $inputs = Inputs::with(['productEquipament.category' => function ($query) {
                        $query->withTrashed();
                    }, 'user' => function ($query) {
                        $query->withTrashed();
                    }, 'storage_location' => function ($query) {
                        $query->withTrashed();
                    }])
                        ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                            $query->whereIn('fk_category_id', $categoryUser)
                                ->withTrashed();
                        })
                        ->where('fk_product_equipament_id', $request->input('product_id'))
                        ->when($request->has('input_id') && ($request->input('input_id') != ''), function ($query) use ($request) {
                            $query->where('id', $request->input('input_id'));
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate(10)
                        ->appends(['product_id' => $request->input('product_id')]);
                }

                $inputs->getCollection()->transform(function ($input) {

                    if ($input->expiration_date && $input->alert) {

                        $expiration_date_for_updating = $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date');

                        $daysRemaining = $this->input->daysUntilDate($expiration_date_for_updating);

                        if (!empty($input->alert)) {
                            $days_for_alerts = $daysRemaining - $input->alert;
                        }

                        if ($daysRemaining >= 1 && $days_for_alerts >= 1) {
                            $status = 'Válido';
                        } elseif ($daysRemaining < 1 && $days_for_alerts < 1) {
                            $status = 'Vencido';
                        } else {
                            $status = 'Em alerta';
                        }

                        $input->status = $status;
                        $input->save();
                    }

                    if ($input->expiration_date == null && $input->alert == null) {
                        $days_for_alerts = null;
                        $daysRemaining = null;
                    }

                    return [
                        'id' => $input->id ?? null,
                        'quantity' => $input->quantity ?? null,
                        'quantity_active' => $input->quantity_active ?? null,
                        'product_name' => $input->productEquipament->trashed()
                            ? $input->productEquipament->name . ' (Deletado)'
                            : $input->productEquipament->name ?? null,
                        'id_product' => $input->productEquipament
                            ? ($input->productEquipament->trashed()
                                ? $input->productEquipament->id
                                : $input->productEquipament->id)
                            : null,
                        'category_name' => $input->productEquipament->category->trashed()
                            ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                            : $input->productEquipament->category->name ?? null,
                        'fk_user_id' => $input->fk_user_id ?? null,
                        'name_user_input' => $input->user->trashed()
                            ? $input->user->name . ' (Deletado)'
                            : $input->user->name ?? null,

                        'date_of_manufacture' => $input->date_of_manufacture
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_manufacture')
                            : null ?? null,
                        'expiration_date' => $input->expiration_date
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date')
                            : null  ?? null,
                        'alert' => $input->alert ?? null,
                        'storage_locations_id' => $input->storage_location?->trashed()
                            ? $input->storage_location->id
                            : $input->storage_location->id ?? null,
                        'storage_locations_name' => $input->storage_location?->trashed()
                            ? $input->storage_location->name . ' (Deletado)'
                            : $input->storage_location->name ?? null,
                        // 'date_of_alert' => $input->date_of_alert
                        //     ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_alert')
                        //     : null,
                        'status' => $input->status ?? null,
                        'days_for_alerts' => $days_for_alerts = $days_for_alerts < 0 ? 0 : $days_for_alerts ?? null,
                        'days_remaining' => $daysRemaining ?? null,
                        'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                        'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                        'deleted_at' => $input && $input->trashed()
                            ? $this->input->getFormattedDate($input, 'deleted_at')
                            : $input->deleted_at ?? null,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Entradas recuperadas com sucesso.',
                    'data' => $inputs,
                ]);
            }

            //ADMIN OR MANAGER

            $inputsAdmin = Inputs::with([
                'productEquipament' => function ($query) {
                    $query->withTrashed();
                },
                'productEquipament.category' => function ($query) {
                    $query->withTrashed();
                },
                'user' => function ($query) {
                    $query->withTrashed();
                },
                'storage_location' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            if ($request->has('product_id') && $request->input('product_id') != '') {

                $inputsAdmin = Inputs::with([
                    'productEquipament' => function ($query) {
                        $query->withTrashed();
                    },
                    'productEquipament.category' => function ($query) {
                        $query->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->withTrashed();
                    },
                    'storage_location' => function ($query) {
                        $query->withTrashed();
                    },
                ])
                    ->where('fk_product_equipament_id', $request->input('product_id'))
                    ->when($request->has('input_id') && ($request->input('input_id') != ''), function ($query) use ($request) {
                        $query->where('id', $request->input('input_id'));
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10)
                    ->appends(['product_id' => $request->input('product_id')]);
            }

            $inputsAdmin->getCollection()->transform(function ($input) {

                if ($input->expiration_date && $input->alert) {

                    $expiration_date_for_updating = $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date');

                    $daysRemaining = $this->input->daysUntilDate($expiration_date_for_updating);

                    if (!empty($input->alert)) {
                        $days_for_alerts = $daysRemaining - $input->alert;
                    }

                    if ($daysRemaining >= 1 && $days_for_alerts >= 1) {
                        $status = 'Válido';
                    } elseif ($daysRemaining < 1 && $days_for_alerts < 1) {
                        $status = 'Vencido';
                    } else {
                        $status = 'Em alerta';
                    }

                    $input->status = $status;
                    $input->save();
                }

                if ($input->expiration_date == null && $input->alert == null) {
                    $days_for_alerts = null;
                    $daysRemaining = null;
                }

                return [
                    'id' => $input->id ?? null,
                    'quantity' => $input->quantity ?? null,
                    'quantity_active' => $input->quantity_active ?? null,
                    'product_name' => $input->productEquipament->trashed()
                        ? $input->productEquipament->name . ' (Deletado)'
                        : $input->productEquipament->name ?? null,
                    'id_product' => $input->productEquipament
                        ? ($input->productEquipament->trashed()
                            ? $input->productEquipament->id
                            : $input->productEquipament->id)
                        : null,
                    'category_name' => $input->productEquipament->category->trashed()
                        ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                        : $input->productEquipament->category->name ?? null,
                    'fk_user_id' => $input->fk_user_id ?? null,
                    'name_user_input' => $input->user->trashed()
                        ? $input->user->name . ' (Deletado)'
                        : $input->user->name ?? null,

                    'date_of_manufacture' => $input->date_of_manufacture ?? null
                        ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_manufacture')
                        : null,
                    'expiration_date' => $input->expiration_date
                        ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date')
                        : null,
                    'alert' => $input->alert ?? null,
                    'storage_locations_id' => $input->storage_location?->trashed()
                        ? $input->storage_location?->id
                        : $input->storage_location?->id ?? null,
                    'storage_locations_name' => $input->storage_location?->trashed()
                        ? $input->storage_location?->name . ' (Deletado)'
                        : $input->storage_location?->name ?? null,
                    // 'date_of_alert' => $input->date_of_alert
                    //     ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_alert')
                    //     : null,
                    'status' => $input->status ?? null,
                    'days_for_alerts' => $days_for_alerts = $days_for_alerts < 0 ? 0 : $days_for_alerts ?? null,
                    'days_remaining' => $daysRemaining ?? null,
                    'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                    'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                    'deleted_at' => $input && $input->trashed()
                        ? $this->input->getFormattedDate($input, 'deleted_at')
                        : $input->deleted_at ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Entradas recuperadas com sucesso.',
                'data' => $inputsAdmin,
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

    public function getIdInputs(Request $request, $id)
    {
        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $idUser)
                ->pluck('fk_category_id')
                ->toArray();

            if ($user->level !== 'admin' && $user->level !== 'manager' && empty($categoryUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $verifyId = $this->input->find($id);

            if (!$verifyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }
            // Verifica o nível de acesso e filtra as entradas
            if ($level == 'user') {

                $inputs = Inputs::withTrashed()
                    ->with([
                        'productEquipament.category' => function ($query) {
                            $query->withTrashed();
                        },
                        'user' => function ($query) {
                            $query->withTrashed();
                        },
                        'storage_location' => function ($query) {
                            $query->withTrashed();
                        },
                        'productEquipament' => function ($query) {
                            $query->withTrashed();
                        },
                    ])
                    ->where('id', $id)
                    ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->withTrashed();
                        $query->whereIn('fk_category_id', $categoryUser);
                    })
                    ->get()
                    ->map(function ($input) {

                        if ($input->expiration_date && $input->alert) {
                        }

                        if ($input->expiration_date == null && $input->alert == null) {
                            $days_for_alerts = null;
                            $daysRemaining = null;
                        }

                        return [
                            'id' => $input->id ?? null,
                            'quantity' => $input->quantity ?? null,
                            'quantity_active' => $input->quantity_active ?? null,
                            'product_name' => $input->productEquipament->trashed()
                                ? $input->productEquipament->name . ' (Deletado)'
                                : $input->productEquipament->name ?? null,
                            'id_product' => $input->productEquipament
                                ? ($input->productEquipament->trashed()
                                    ? $input->productEquipament->id
                                    : $input->productEquipament->id)
                                : null,
                            'category_name' => $input->productEquipament->category->trashed()
                                ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                                : $input->productEquipament->category->name ?? null,
                            'fk_user_id' => $input->fk_user_id ?? null,
                            'name_user_input' => $input->user->trashed()
                                ? $input->user->name . ' (Deletado)'
                                : $input->user->name ?? null,

                            'date_of_manufacture' => $input->date_of_manufacture
                                ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_manufacture')
                                : null,
                            'expiration_date' => $input->expiration_date
                                ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date')
                                : null,
                            'alert' => $input->alert,
                            'storage_locations_id' => $input->storage_location?->trashed()
                                ? $input->storage_location->id
                                : $input->storage_location->id ?? null,
                            'storage_locations_name' => $input->storage_location?->trashed()
                                ? $input->storage_location->name . ' (Deletado)'
                                : $input->storage_location->name ?? null,
                            // 'date_of_alert' => $input->date_of_alert
                            //     ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_alert')
                            //     : null,
                            'status' => $input->status,
                            'days_for_alerts' => $days_for_alerts = $days_for_alerts < 0 ? 0 : $days_for_alerts,
                            'days_remaining' => $daysRemaining,
                            'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                            'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                            'deleted_at' => $input && $input->trashed()
                                ? $this->input->getFormattedDate($input, 'deleted_at')
                                : $input->deleted_at ?? null,
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada recuperada com sucesso.',
                    'data' => $inputs,
                ]);
            }

            //ADMIN OR MANAGER

            $inputsAdmin = Inputs::withTrashed()
                ->with([
                    'productEquipament.category' => function ($query) {
                        $query->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->withTrashed();
                    },
                    'storage_location' => function ($query) {
                        $query->withTrashed();
                    },
                    'productEquipament' => function ($query) {
                        $query->withTrashed();
                    },
                ])
                ->where('id', $id)
                // ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                //     // $query->whereIn('fk_category_id', $categoryUser);
                // })
                ->get()
                ->map(function ($input) {

                    if ($input->expiration_date && $input->alert) {

                        $this->input_service->updateStatusInput($input);
                    }

                    if ($input->expiration_date == null && $input->alert == null) {
                        $days_for_alerts = null;
                        $daysRemaining = null;
                    }

                    return [
                        'id' => $input->id ?? null,
                        'quantity' => $input->quantity ?? null,
                        'quantity_active' => $input->quantity_active ?? null,
                        'product_name' => $input->productEquipament->trashed()
                            ? $input->productEquipament->name . ' (Deletado)'
                            : $input->productEquipament->name ?? null,
                        'id_product' => $input->productEquipament
                            ? ($input->productEquipament->trashed()
                                ? $input->productEquipament->id
                                : $input->productEquipament->id)
                            : null,
                        'category_name' => $input->productEquipament->category->trashed()
                            ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                            : $input->productEquipament->category->name ?? null,
                        'fk_user_id' => $input->fk_user_id ?? null,
                        'name_user_input' => $input->user->trashed()
                            ? $input->user->name . ' (Deletado)'
                            : $input->user->name ?? null,

                        'date_of_manufacture' => $input->date_of_manufacture
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_manufacture')
                            : null,
                        'expiration_date' => $input->expiration_date
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date')
                            : null,
                        'alert' => $input->alert,
                        'storage_locations_id' => $input->storage_location?->trashed()
                            ? $input->storage_location->id
                            : $input->storage_location->id ?? null,
                        'storage_locations_name' => $input->storage_location?->trashed()
                            ? $input->storage_location->name . ' (Deletado)'
                            : $input->storage_location->name ?? null,
                        // 'date_of_alert' => $input->date_of_alert
                        //     ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_alert')
                        //     : null,
                        'status' => $input->status,
                        'days_for_alerts' => $days_for_alerts = $days_for_alerts < 0 ? 0 : $days_for_alerts,
                        'days_remaining' => $daysRemaining,
                        'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                        'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                        'deleted_at' => $input && $input->trashed()
                            ? $this->input->getFormattedDate($input, 'deleted_at')
                            : $input->deleted_at ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Entrada recuperada com sucesso.',
                'data' => $inputsAdmin,
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

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->user();
            $idUser = $user->id;

            if (empty($request->date_of_manufacture) && empty($request->expiration_date)) {
                // dd('aqui 1');
                $validatedData = $request->validate(
                    $this->input->rulesInputs(),
                    $this->input->feedbackInputs()
                );
            } elseif (!empty($request->date_of_manufacture) && empty($request->expiration_date)) {
                // dd('aqui 2');
                $validatedData = $request->validate(
                    $this->input->rulesInputsDateOfManufacture(),
                    $this->input->feedbackInputsDateOfManufacture()
                );
            } else {
                // dd('aqui 3');
                $validatedData = $request->validate(
                    $this->input->rulesInputsExpirationDate(),
                    $this->input->feedbackInputsExpirationDate()
                );
            }

            if ($validatedData) {
                $input = $this->input->create([
                    'fk_product_equipament_id' => $request->fk_product_equipament_id,
                    'quantity' => $request->quantity,
                    'fk_user_id' => $user->id,
                    'fk_storage_locations_id' => $request->fk_storage_locations_id,
                    'date_of_manufacture' => $request->date_of_manufacture,
                    'expiration_date' => $request->expiration_date,
                    'alert' => $request->alert,
                    'quantity_active' => $request->quantity,
                ]);
            }

            if ($input) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'inputs',
                    'record_id' => $input->id,
                    'description' => 'Adicionou uma entrada.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada criada com sucesso.',
                    'data' => $input,
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            $idUser = $user->id;
            $date = now();

            $updateInput = $this->input->find($id);

            if (!$updateInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada.',
                ]);
            }

            $originalData = $updateInput->getOriginal();

            if (empty($request->date_of_manufacture) && empty($request->expiration_date)) {
                // dd('aqui 1');
                $validatedData = $request->validate(
                    $this->input->rulesInputs(),
                    $this->input->feedbackInputs()
                );
            } elseif (!empty($request->date_of_manufacture) && empty($request->expiration_date)) {
                // dd('aqui 2');
                $validatedData = $request->validate(
                    $this->input->rulesInputsDateOfManufacture(),
                    $this->input->feedbackInputsDateOfManufacture()
                );
            } else {
                // dd('aqui 3');
                $validatedData = $request->validate(
                    $this->input->rulesInputsExpirationDate(),
                    $this->input->feedbackInputsExpirationDate()
                );
            }

            $sum = Exits::where('fk_inputs_id', $id)->where('fk_product_equipament_id', $updateInput->fk_product_equipament_id)->sum('quantity');

            // dd('realizar validação do updateInput que não pode ser um valor maior que a quantidade que já saiu.');

            if ($request->quantity < $sum) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível alterar entrada para esse valor.',
                ]);
            }

            if ($request->quantity != $updateInput->quantity) {
                $updateInput->quantity_active = $request->quantity;
            }

            $updateInput->fill($validatedData);
            $updateInput->save();

            // Verificando as mudanças e criando a string de log
            $changes = $updateInput->getChanges(); // Retorna apenas os campos que foram alterados
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
                'table_name' => 'inputs',
                'record_id' => $id,
                'description' => 'Atualizou uma entrada. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            if ($updateInput) {

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada atualizada com sucesso.',
                    'data' => $updateInput,
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

    public function delete(Request $request, $id)
    {
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

            $deleteInput = $this->input->find($id);

            if (!$deleteInput) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $deleteInput->delete();

            if ($deleteInput) {
                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'inputs',
                    'record_id' => $id,
                    'description' => 'Excluiu uma entrada.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada removida com sucesso.',
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

    public function inputsInAlerts(Request $request)
    {
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

            $getAllInputsForUpdating = Inputs::with([
                'productEquipament' => function ($query) {
                    $query->withTrashed();
                },
                'productEquipament.category' => function ($query) {
                    $query->withTrashed();
                },
                'user' => function ($query) {
                    $query->withTrashed();
                },
                'storage_location' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $getAllInputsForUpdating->getCollection()->transform(function ($input) {

                if ($input->expiration_date && $input->alert) {

                    $this->input_service->updateStatusInput($input);
                }
            });

            $inputsInAlerts = Inputs::withTrashed()
                ->with([
                    'productEquipament.category' => function ($query) {
                        $query->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->withTrashed();
                    },
                    'storage_location' => function ($query) {
                        $query->withTrashed();
                    },
                ])
                ->where('status', 'Em alerta')
                ->select('*', DB::raw('DATEDIFF(expiration_date, NOW()) AS days_remaining')) // Calcule os dias restantes
                ->orderBy('days_remaining', 'asc') // Ordene pelos dias restantes em ordem crescente
                ->get()
                ->map(function ($input) {
                    if ($input->expiration_date && $input->alert) {

                        $this->input_service->updateStatusInput($input);
                    }

                    if ($input->expiration_date == null && $input->alert == null) {
                        $days_for_alerts = null;
                        $daysRemaining = null;
                    }

                    return [
                        'id' => $input->id ?? null,
                        'quantity' => $input->quantity ?? null,
                        'quantity_active' => $input->quantity_active ?? null,
                        'product_name' => $input->productEquipament->trashed()
                            ? $input->productEquipament->name . ' (Deletado)'
                            : $input->productEquipament->name ?? null,
                        'id_product' => $input->productEquipament
                            ? ($input->productEquipament->trashed()
                                ? $input->productEquipament->id
                                : $input->productEquipament->id)
                            : null,
                        'category_name' => $input->productEquipament->category->trashed()
                            ? $input->productEquipament->category->name . ' (Deletado)' // Se deletado (Deletado)
                            : $input->productEquipament->category->name ?? null,
                        'fk_user_id' => $input->fk_user_id ?? null,
                        'name_user_input' => $input->user->trashed()
                            ? $input->user->name . ' (Deletado)'
                            : $input->user->name ?? null,

                        'date_of_manufacture' => $input->date_of_manufacture
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_manufacture')
                            : null,
                        'expiration_date' => $input->expiration_date
                            ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date')
                            : null,
                        'alert' => $input->alert,
                        'storage_locations_id' => $input->storage_location?->trashed()
                            ? $input->storage_location->id . ' (Deletado)'
                            : $input->storage_location->id ?? null,
                        'storage_locations_name' => $input->storage_location?->trashed()
                            ? $input->storage_location->name . ' (Deletado)'
                            : $input->storage_location->name ?? null,
                        // 'date_of_alert' => $input->date_of_alert
                        //     ? $this->input->getFormatteDateofManufactureOrExpiration($input, 'date_of_alert')
                        //     : null,
                        'status' => $input->status,
                        'days_for_alerts' => $days_for_alerts = $days_for_alerts < 0 ? 0 : $days_for_alerts,
                        'days_remaining' => $daysRemaining,
                        'created_at' => $this->input->getFormattedDate($input, 'created_at') ?? null,
                        'updated_at' => $this->input->getFormattedDate($input, 'updated_at') ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Entrada(s) em alerta recuperada(s) com sucesso.',
                'data' => $inputsInAlerts,
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

    public function getInputsWithOrderByExpirationDate(Request $request, $id)
    {
        $inputService = $this->input_service->getInputsWithOrderByExpirationDate($request, $id);

        return $inputService;
    }
}