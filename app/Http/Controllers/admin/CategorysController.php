<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Models\Category;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategorysController extends CrudController
{
    protected $category;

    public function __construct(Category $category)
    {
        parent::__construct($category);
        $this->category = $category;
    }
    public function getAllCategorys(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $getAllCategorys = Category::all();

            if ($getAllCategorys) {
                return response()->json([
                    'success' => true,
                    'message' => 'Categorias recuperadas com sucesso.',
                    'data' => $getAllCategorys,
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

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $getIdcategory = Category::where('id', $id)->first();

            if (!$getIdcategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categoria recuperado com sucesso.',
                'data' => $getIdcategory,
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
        try {
            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $validatedData = $request->validate(
                $this->category->rulesCategory(),
                $this->category->feedbackCategory(),
            );

            $name = $request->name;
            $description = $request->description;

            $createCategory = $validatedData;

            $createCategory = $this->category->create([
                'name' => $name,
                'description' => $description,
            ]);

            if ($createCategory) {

                DB::table('category_user')->insert([
                    'fk_user_id' => 1,
                    'fk_category_id' => $createCategory['id'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Categoria criada com sucesso.',
                    'data' => $createCategory,
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

    public function update(Request $request, $id)
    {
        try {

            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $category = $this->category->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.',
                ]);
            }

            $validatedData = $request->validate(
                $this->category->rulesCategory(),
                $this->category->feedbackCategory(),
            );

            $category->fill($validatedData);
            $category->save();

            if ($category) {
                return response()->json([
                    'success' => true,
                    'message' => 'Categoria atualizada com sucesso.',
                    'data' => $category,
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

    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user->tokenCan('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão de acesso para seguir adiante.',
                ]);
            }

            $deleteCategory = $this->category->find($id);

            if (!$deleteCategory) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhum resultado encontrado.",
                ]);
            }

            $formatedDate = now();

            $deleteCategory->delete();

            if ($deleteCategory) {
                $data = DB::table('category_user')->where('fk_category_id', $id)->get();

                $dataTwo = DB::table('products_equipaments')->where('fk_category_id', $id)->get();

                if ($data) {
                    DB::table('category_user')->where('fk_category_id', $id)->update(['deleted_at' => $formatedDate]);
                }

                if ($dataTwo) {
                    DB::table('products_equipaments')->where('fk_category_id', $id)->update(['deleted_at' => $formatedDate]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Categoria removida com sucesso.',
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