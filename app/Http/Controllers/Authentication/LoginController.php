<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
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

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => $token,
        ]);
    }
}