<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\CrudController;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends CrudController
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getAll(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $getAllUser = User::all();

            if ($getAllUser) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuários recuperados com sucesso.',
                    'data' => $getAllUser,
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
    public function getId(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }


            $getIdUser = User::where('id', $id)->first();

            if (!$getIdUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuário recuperado com sucesso.',
                'data' => $getIdUser,
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
        try {

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $create = $request->validate(
                $this->user->rulesCreateUser(),
                $this->user->feedbackCreateUser(),

            );

            $name = $request->name;
            $email = $request->email;
            $password = $request->password;


            $create = $this->user->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            if ($create) {
                return response()->json([
                    'success' => true,
                    'message' => "Usuário criado com sucesso.",
                    'data' => $create,
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

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $updateUser = $this->user->find($id);

            if (!$updateUser) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $validateModel = $request->validate(
                $this->user->rulesCreateUser(),
                $this->user->feedbackCreateUser()
            );

            $updateUser->fill($validateModel);
            $updateUser->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso.',
                'data' => $updateUser,
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

    public function delete(Request $request, $id)
    {
        try {

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteUser = $this->user->find($id);

            if (!$deleteUser) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $formatedDate = now();

            $deleteUser->delete();

            if ($deleteUser) {
                DB::table('category_user')->where('fk_user_id', $id)->update(['deleted_at' => $formatedDate]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuário removido com sucesso.',
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

    public function assignCategoryUser(Request $request, $id)
    {
        try {

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $validatedData = $request->validate(
                $this->user->rulesCategoryUser(),
                $this->user->feedbackCategoryUser()
            );

            $user = $this->user->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $user->categories()->sync($validatedData['responsible_category']);

            if ($user) {
                User::where('id', $id)->update(['responsible_category' => $request->input('responsible_category')]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categorias atribuídas com sucesso.',
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

    public function updateLevel(Request $request, $id)
    {
        try {

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $userUpdate = User::where('id', $id)->first();

            if (!$userUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $validatedData = $request->validate(
                $this->user->rulesUpdateLevelUser(),
                $this->user->feedbackUpdateLevelUser()
            );


            $userUpdate->fill($validatedData);
            $userUpdate->save();

            return response()->json([
                'success' => true,
                'message' => 'Nível de permissão atualizado com sucesso.',
                'data' => $userUpdate,
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
}