<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\Register;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    protected $register;

    public function __construct(Register $register)
    {
        $this->register = $register;
    }

    public function register(Request $request)
    {
        try {
            $register = $request->validate($this->register->rulesRegister(), $this->register->feedbackRegister());

            if ($register) {
                $register = $this->register->create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }

            if (!$register) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao registrar',
                ]);
            }

            if ($register) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registrado com sucesso',
                    'data' => ['dataUser' => $register],
                ]);
            }
        } catch (QueryException $qe) {
            return response()->json([
                'success' => false,
                'message' => 'Error DB: ' . $qe->getMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}