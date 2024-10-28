<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];

    public function rulesRegister()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users|string',
            'password' => 'required|min:8',
        ];
    }

    public function feedbackRegister()
    {
        return [
            'required' => 'Campo obrigatório.',
            'email' => 'Insira um email válido.',
            'email.unique' => 'Este email já está registrado.',
            'min' => 'A senha deve ter no mínimo 8 caracteres.',
        ];
    }
}