<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'system_logs';
    protected $fillable = [
        'fk_user_id',
        'action',
        'table_name',
        'record_id',
        'description',
    ];
}