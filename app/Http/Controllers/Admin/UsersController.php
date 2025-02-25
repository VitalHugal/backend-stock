<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Models\CategoryUser;
use App\Models\SystemLog;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

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
            $level = $user->level;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            if ($request->has('active') && $request->input('active') == 'true') {
                $getAllUser = User::all();
            } elseif ($request->has('active') && $request->input('active') == 'false') {
                $getAllUser = User::withTrashed()->whereNotNull('deleted_at')->get();
            } else {
                $getAllUser = User::withTrashed()->get();
            }

            // $getAllUser = User::withTrashed()->get();

            $getAllUser = $getAllUser->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->trashed()
                        ? $user->name . ' (Deletado)'
                        : $user->name ?? null,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'level' => $user->level,
                    'reservation_enabled' => $user->reservation_enabled,
                    'created_at' => $this->user->getFormattedDate($user, 'created_at'),
                    'updated_at' => $this->user->getFormattedDate($user, 'updated_at'),
                    'deleted_at' => $user->deleted_at
                        ? $this->user->getFormattedDate($user, 'deleted_at')
                        : null,
                ];
            });

            if ($getAllUser->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

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

            $level = $user->level;

            if ($level == 'user' || $level == 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            // $getIdUser = User::where('id', $id)->first();

            $getIdUser = User::withTrashed()->where('id', $id)->get();

            if ($getIdUser->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $getIdUser = $getIdUser->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->trashed()
                        ? $user->name . ' (Deletado)'
                        : $user->name ?? null,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'level' => $user->level,
                    'reservation_enabled' => $user->reservation_enabled,
                    'created_at' => $this->user->getFormattedDate($user, 'created_at'),
                    'updated_at' => $this->user->getFormattedDate($user, 'updated_at'),
                    'deleted_at' => $user->deleted_at
                        ? $this->user->getFormattedDate($user, 'deleted_at')
                        : null,
                ];
            });

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

            $name = $request->name;
            $email = $request->email;
            $password = $request->password;

            $emailExists = User::where('email', $email)->first();

            if ($emailExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este e-mail já está cadastrado. Tente novamente com um e-mail diferente.',
                ]);
            }

            $create = $request->validate($this->user->rulesCreateUser(), $this->user->feedbackCreateUser());

            $create = $this->user->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            if ($create) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'users',
                    'record_id' => $create->id,
                    'description' => 'Adicionou um usuário.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Usuário criado com sucesso.",
                    'data' => $create,
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
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user' || $level == 'manager') {
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

            $originalData = $updateUser->getOriginal();

            $verifyEmail = User::where('email', $request->email)->first();
            if ($verifyEmail) {
                if ($verifyEmail->id != $id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'E-mail indiponível, tente novamente.',
                    ]);
                }
            }

            $validateModel = $request->validate(
                $this->user->rulesUpdateUser(),
                $this->user->feedbackUpdateUser()
            );

            $updateUser->fill($validateModel);
            $updateUser->save();

            // Verificando as mudanças e criando a string de log
            $changes = $updateUser->getChanges(); // Retorna apenas os campos que foram alterados
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
                'table_name' => 'users',
                'record_id' => $id,
                'description' => 'Atualizou um usuário. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso.',
                'data' => $updateUser,
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

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'users',
                    'record_id' => $id,
                    'description' => 'Excluiu um usuário',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Usuário removido com sucesso.',
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

    public function reverseDeletedUser(Request $request, $id)
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

            $user = User::withTrashed()->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }
            if ($user->deleted_at == false) {

                return response()->json([
                    'success' => true,
                    'message' => 'Não foi possível executar essa ação, usuário não pertence aos deletados.',
                ]);
            }

            $user->restore();

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Restaurou',
                'table_name' => 'users',
                'record_id' => $id,
                'description' => 'Retornou usuário deletado aos ativos.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuário retornou aos ativos.',
                'data' => $user
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

    public function assignCategoryUser(Request $request, $id)
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

            $originalData = $user->getOriginal();

            $user->categories()->sync($validatedData['responsible_category']);

            if ($user) {

                // Verificando as mudanças e criando a string de log
                $changes = $user->getChanges(); // Retorna apenas os campos que foram alterados
                $logDescription = '';

                foreach ($changes as $key => $newValue) {
                    $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                    $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
                }

                if ($logDescription == null) {
                    $logDescription = 'Nenhum.';
                }

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Atualizou',
                    'table_name' => 'category_user',
                    'record_id' => $id,
                    'description' => 'Atualizou o(s) setor(es) de acesso do usuário. Dados alterados: ' . $logDescription,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Categorias atribuídas com sucesso.',
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

    public function updateLevel(Request $request, $id)
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

            $userUpdate = User::where('id', $id)->first();

            if (!$userUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $originalData = $userUpdate->getOriginal();

            $validatedData = $request->validate(
                $this->user->rulesUpdateLevelUser(),
                $this->user->feedbackUpdateLevelUser()
            );


            $userUpdate->fill($validatedData);
            $userUpdate->save();

            $changes = $userUpdate->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'users',
                'record_id' => $id,
                'description' => 'Atualizou o nivel de acesso do usuário. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            if ($userUpdate->level == 'admin') {
                User::where('id', $id)->update(['reservation_enabled' => 1]);
            } else {
                User::where('id', $id)->update(['reservation_enabled' => 0]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nível de permissão atualizado com sucesso.',
                'data' => User::find($id),
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

    public function updateReservationEnable(Request $request, $id)
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

            $userUpdate = User::where('id', $id)->first();

            if (!$userUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $originalData = $userUpdate->getOriginal();

            $validatedData = $request->validate(
                $this->user->rulesReservationEnable(),
                $this->user->feedbackReservationEnable()
            );

            $userUpdate->fill($validatedData);
            $userUpdate->save();

            $changes = $userUpdate->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'users',
                'record_id' => $id,
                'description' => 'Atualizou usuário, permitindo acesso a reservas. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nível de acesso atualizado com sucesso.',
                'data' => $userUpdate,
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

    public function myProfile(Request $request)
    {
        try {
            $myProfile = $request->user();

            $categoryUser = DB::table('category_user')
                ->where('fk_user_id', $myProfile->id)
                ->pluck('fk_category_id')
                ->toArray();

            $categories = DB::table('category')
                ->whereIn('id', $categoryUser)
                ->pluck('name');

            $result = [
                "id" =>  $myProfile->id,
                "name" =>  $myProfile->name,
                "email" =>  $myProfile->email,
                "email_verified_at" =>  $myProfile->email_verified_at,
                "level" =>  $myProfile->level,
                "reservation_enabled" =>  $myProfile->reservation_enabled,
                "created_at" =>  $this->user->getFormattedDate($myProfile, 'created_at'),
                "updated_at" =>  $this->user->getFormattedDate($myProfile, 'updated_at'),
                'categories' => $categories
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => $result
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

    //USER
    public function updatePassword(Request $request)
    {
        try {
            $oldPassword = $request->user()->password;
            $userId = $request->user()->id;

            $validatedData = $request->validate(
                $this->user->rulesUpdatePassword(),
                $this->user->feedbackUpdatePassword()
            );

            // keys
            // password
            // password_confirmation

            if (Hash::check($request->password, $oldPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha já utilizada anteriormente.',
                ]);
            }

            $newPassword = User::where('id', $userId)
                ->update(['password' => Hash::make($request->password)]);

            if ($newPassword) {

                SystemLog::create([
                    'fk_user_id' => $userId,
                    'action' => 'Atualizou',
                    'table_name' => 'users',
                    'record_id' => $userId,
                    'description' => 'Atualizou a própria senha.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Senha alterada com sucesso.',
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

    public function updatePasswordAdmin(Request $request, $id)
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

            $userResetPassword = User::where('id', $id)->first();

            if (!$userResetPassword) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado.',
                ]);
            }

            $validatedData = $request->validate(
                $this->user->rulesUpdatePasswordAdmin(),
                $this->user->feedbackUpdatePasswordAdmin()
            );

            $password = Hash::make($request->password);

            $userResetPassword->update(['password' => $password]);
            $userResetPassword->save();

            if ($userResetPassword) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Atualizou',
                    'table_name' => 'users',
                    'record_id' => $id,
                    'description' => 'Atualizou a senha do usuário.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Senha alterada com sucesso',
                    // 'data' => $request->password,
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

    public function viewCategoryUser(Request $request, $id)
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

            $viewCategoryUser = $this->user->find($id);

            if (!$viewCategoryUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $categoryUser = CategoryUser::where('fk_user_id', $id)->with('category')->get();

            $categoryUser = $categoryUser->map(function ($sector) {
                return [
                    'id-category' => $sector->category->id,
                    'name-category' => $sector->category ? $sector->category->name : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => $categoryUser,
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