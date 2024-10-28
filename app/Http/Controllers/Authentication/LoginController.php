<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CrudController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends CrudController
{
    public function login(Request $request)
    {
        $login = User::where('email', $request->email)->first();

        if (!$login) {
            return response()->json([
              'success' => false,
              'message' => 'Nenhum usuário encontrado.'  
            ]);
        }

        $password = $login->password;
        $requestPassword = $request->password;

        $verifyPassword = Hash::check($requestPassword, $password);

        dd($verifyPassword);
    }
}