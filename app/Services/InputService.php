<?php

namespace App\Services;

use App\Models\Inputs;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InputService
{

    protected $input;

    public function __construct(Inputs $inputs)
    {
        $this->input = $inputs;
    }

    public function getInputsWithOrderByExpirationDate($request, $id)
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
            if ($level == 'user') {
                try {
                    $getAllInputsForUpdatingUser = Inputs::with([
                        'productEquipament.category' => function ($query) {
                            $query->withTrashed();
                        },
                        'user' => function ($query) {
                            $query->withTrashed();
                        },
                        'storage_location' => function ($query) {
                            $query->withTrashed();
                        }
                    ])
                        ->whereHas('productEquipament', function ($query) use ($categoryUser) {
                            $query->whereIn('fk_category_id', $categoryUser)
                                ->withTrashed();
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

                    $getAllInputsForUpdatingUser->getCollection()->transform(function ($input) {

                        if ($input->expiration_date && $input->alert) {

                            $expiration_date_for_updating = $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date');

                            $days_remaining_for_updating = $this->input->daysUntilDate($expiration_date_for_updating);

                            if (!empty($input->alert)) {
                                $days_for_alerts_for_updating = $days_remaining_for_updating - $input->alert;
                            }

                            if ($days_remaining_for_updating >= 1 && $days_for_alerts_for_updating >= 1) {
                                $status = 'Válido';
                            } elseif ($days_remaining_for_updating < 1 && $days_for_alerts_for_updating < 1) {
                                $status = 'Vencido';
                            } else {
                                $status = 'Em alerta';
                            }

                            $input->status = $status;
                            $input->save();
                        }
                    });


                    $inputsInAlerts = Inputs::with([
                        'productEquipament.category' => function ($query) {
                            $query->withTrashed();
                        },
                        'user' => function ($query) {
                            $query->withTrashed();
                        },
                        'storage_location' => function ($query) {
                            $query->withTrashed();
                        },
                    ])->whereHas('productEquipament', function ($query) use ($categoryUser) {
                        $query->whereIn('fk_category_id', $categoryUser)
                            ->withTrashed();
                    })
                        ->where('fk_product_equipament_id', $id)
                        // ->where('status', '!=', 'Vencido')
                        ->where('status', '!=', 'Finalizado')
                        ->where('quantity_active', '==', '0')
                        ->select('*', DB::raw('DATEDIFF(expiration_date, NOW()) AS days_remaining')) // Calcule os dias restantes
                        ->orderBy('days_remaining', 'asc') // Ordene pelos dias restantes em ordem crescente
                        ->get()
                        ->map(function ($input) {
                            if ($input->expiration_date && $input->alert) {
                                $daysRemaining = $input->days_remaining;

                                if (!empty($input->alert)) {
                                    $days_for_alerts = $daysRemaining - $input->alert;
                                }
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
                                'storage_locations_id' => $input->storage_location->trashed()
                                    ? $input->storage_location->id . ' (Deletado)'
                                    : $input->storage_location->id ?? null,
                                'storage_locations_name' => $input->storage_location->trashed()
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


                    if ($inputsInAlerts->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nenhum resultado encontrado.',
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Entrada(s) em ordem de validade recuperada(s) com sucesso.',
                        'data' => $inputsInAlerts[0],
                    ]);

                    // return $inputsInAlerts[0];

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

            //ADMIN OR MANAGER

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

                    $expiration_date_for_updating = $this->input->getFormatteDateofManufactureOrExpiration($input, 'expiration_date');

                    $days_remaining_for_updating = $this->input->daysUntilDate($expiration_date_for_updating);

                    if (!empty($input->alert)) {
                        $days_for_alerts_for_updating = $days_remaining_for_updating - $input->alert;
                    }

                    if ($days_remaining_for_updating >= 1 && $days_for_alerts_for_updating >= 1) {
                        $status = 'Válido';
                    } elseif ($days_remaining_for_updating < 1 && $days_for_alerts_for_updating < 1) {
                        $status = 'Vencido';
                    } else {
                        $status = 'Em alerta';
                    }

                    $input->status = $status;
                    $input->save();
                }
            });

            $inputsInAlerts = Inputs::with([
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
                ->where('fk_product_equipament_id', $id)
                // ->where('status', '!=', 'Vencido')
                ->where('status', '!=', 'Finalizado')
                ->where('quantity_active', '>', '0')
                ->whereNotNull('quantity_active')
                ->select('*', DB::raw('DATEDIFF(expiration_date, NOW()) AS days_remaining')) // Calcule os dias restantes
                ->orderBy('days_remaining', 'asc') // Ordene pelos dias restantes em ordem crescente
                ->get()
                ->map(function ($input) {
                    if ($input->expiration_date && $input->alert) {
                        $daysRemaining = $input->days_remaining;

                        if (!empty($input->alert)) {
                            $days_for_alerts = $daysRemaining - $input->alert;
                        }
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
                        'storage_locations_id' => $input->storage_location->trashed()
                            ? $input->storage_location->id . ' (Deletado)'
                            : $input->storage_location->id ?? null,
                        'storage_locations_name' => $input->storage_location->trashed()
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

            if ($inputsInAlerts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma entrada encontrada, faça novas entradas ou verifique.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Entrada(s) em ordem de validade recuperada(s) com sucesso.',
                'data' => $inputsInAlerts[0],
            ]);

            // return $inputsInAlerts[0];

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

    function updateStatusInput($model)
    {

        $expiration_date_for_updating = $this->input->getFormatteDateofManufactureOrExpiration($model, 'expiration_date');

        $days_remaining_for_updating = $this->input->daysUntilDate($expiration_date_for_updating);

        if (!empty($model->alert)) {
            $days_for_alerts_for_updating = $days_remaining_for_updating - $model->alert;
        }

        if ($days_remaining_for_updating >= 1 && $days_for_alerts_for_updating >= 1) {
            $status = 'Válido';
        } elseif ($days_remaining_for_updating < 1 && $days_for_alerts_for_updating < 1) {
            $status = 'Vencido';
        } else {
            $status = 'Em alerta';
        }

        $model->status = $status;
        $model->save();
    }
}