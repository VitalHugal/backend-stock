<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;

class ExitsController extends CrudController
{
    protected $exits;

    public function __construct(Exits $exits)
    {
        parent::__construct($exits);
        
        $this->exits = $exits;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $idUser = $user->id;

        $categoryUser = DB::table('category_user')->where('fk_user_id', $idUser)->get();
    }
}