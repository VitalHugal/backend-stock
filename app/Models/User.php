<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password', 'level', 'responsible_category'];
    protected $table = 'users';
    protected $dates = 'deleted_at';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function rulesCreateUser()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'email|required|max:255',
            'password' => 'required|min:8',
            'responsible_category' => '',
            // 'fk_category_id' => 'required|exists:category,id',
        ];
    }

    public function feedbackCreateUser()
    {
        return [
            'required' => 'Campo obrigatório.',
            'max:255' => 'O campo deve ter no máximo 255 caracteres.',
            'email' => 'E-mail inválido.',
            'min:8' => 'O campo deve ter no mínimo 8 carcateres.',
            // 'exists' => 'Categoria não encontrada verifique.',
        ];
    }
    public function rulesCategoryUser()
    {
        return [
            'responsible_category' => 'required|array|exists:category,id',
            // 'fk_category_id' => 'required|exists:category,id',
        ];
    }

    public function feedbackCategoryUser()
    {
        return [
            'required' => 'Campo obrigatório.',
            'array' => 'Formato inválido (necessário array).',
            'exists' => 'Categoria não encontrada verifique.',
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_user', 'fk_user_id', 'fk_category_id');
    }
}