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
    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'reservation_enabled',
        // 'responsible_category'
    ];
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

    function getFormattedDate($model, $params)
    {
        $formatedDateWithdrawalDate = explode(" ", $model->$params);
        $formatedHoursWithdrawalDate = $formatedDateWithdrawalDate[1];
        $formatedDateWithdrawalDate = explode('-', $formatedDateWithdrawalDate[0]);
        return $formatedDateWithdrawalDate[2] . '/' . $formatedDateWithdrawalDate[1] . '/' . $formatedDateWithdrawalDate[0] . ' ' . $formatedHoursWithdrawalDate;
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function rulesCreateUser()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'email|required|max:255|',
            'password' => 'required|min:8',
            // 'responsible_category' => '',
        ];
    }

    public function feedbackCreateUser()
    {
        return [
            'required' => 'Campo obrigatório.',
            'max:255' => 'O campo deve ter no máximo 255 caracteres.',
            'email' => 'E-mail inválido.',
            'min:8' => 'O campo deve ter no mínimo 8 carcateres.',
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////
    public function rulesUpdateUser()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'email|required|max:255|',
            // 'responsible_category' => '',
        ];
    }

    public function feedbackUpdateUser()
    {
        return [
            'required' => 'Campo obrigatório.',
            'max:255' => 'O campo deve ter no máximo 255 caracteres.',
            'email' => 'E-mail inválido.',
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function rulesCategoryUser()
    {
        return [
            'responsible_category' => 'nullable|array|exists:category,id',
        ];
    }

    public function feedbackCategoryUser()
    {
        return [
            'array' => 'Formato inválido (necessário array).',
            'exists' => 'Categoria não encontrada verifique.',
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function rulesUpdateLevelUser()
    {
        return [
            'level' => 'required|in:admin,user',
        ];
    }

    public function feedbackUpdateLevelUser()
    {
        return [
            'required' => 'Campo obrigatório.',
            'in' => 'Valido apenas admin ou user para esse campo.',
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function rulesUpdatePassword()
    {
        return [
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function feedbackUpdatePassword()
    {
        return [
            'password.required' => 'Campo obrigatório.',
            'password.confirmed' => 'Senhas divergentes!',
            'password.min' => 'A senha deve ter no minímo 8 caracteres',
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function rulesUpdatePasswordAdmin()
    {
        return [
            'password' => 'required|min:8',
        ];
    }
    public function feedbackUpdatePasswordAdmin()
    {
        return [
            'password.required' => 'Campo obrigatório.',
            'password.min' => 'A senha deve ter no minímo 8 caracteres',
        ];
    }
    ///////////////////////////////////////////////////////////////////////////////

    public function rulesReservationEnable()
    {
        return [
            'reservation_enabled' => 'required|boolean',
        ];
    }
    public function feedbackReservationEnable()
    {
        return [
            'required' => 'Campo obrigatório.',
            'boolean' => 'Válido apenas 0 ou 1 nesse campo.',
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_user', 'fk_user_id', 'fk_category_id');
    }
}