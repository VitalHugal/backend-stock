<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-mail ou senha inválidos.'
                ]);
            }

            $token = $user;
            $token->tokens()->delete();
            $token = null;

            if ($user->level === 'admin') {
                $token = $user->createToken('AdminToken', ['admin'])->plainTextToken;
            } else {
                $token = $user->createToken('UserToken')->plainTextToken;
            }
            $token = Str::of($token)->explode('|')[1];

            if ($token) {

                SystemLog::create([
                    'fk_user_id' => $user->id,
                    'action' => 'Entrou',
                    'description' => 'Usuário entrou.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Login realizado com sucesso.',
                    'data' => $token,
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