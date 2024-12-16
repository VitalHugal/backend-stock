<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Models\Category;
use App\Models\SystemLog;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

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
            $level = $user->level;

            if ($level == 'user') {

                $categoryUser = DB::table('category_user')
                    ->where('fk_user_id', $user->id)
                    ->pluck('fk_category_id')
                    ->toArray();

                if ($categoryUser == null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você não tem permissão de acesso para seguir adiante.',
                    ]);
                }

                $categoryAccessUser = Category::whereIn('id', $categoryUser)
                    ->orderBy('id', 'asc')
                    ->get();

                // $categoryAccessUser = Category::withTrashed()
                //     ->whereIn('id', $categoryUser)
                //     ->orderBy('id', 'asc')
                //     ->get();


                $categoryAccessUser->transform(function ($category) {

                    return [
                        'id' => $category->id,
                        'name' => $category->trashed()
                            ? $category->name . ' (Deletado)'
                            : $category->name,
                        'description' => $category->description,
                        'created_at' => $this->category->getFormattedDate($category, 'created_at'),
                        'updated_at' => $this->category->getFormattedDate($category, 'updated_at'),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Categorias de usuário recuperadas com sucesso.',
                    'data' => $categoryAccessUser,
                ]);
            }

            if ($user->level == 'admin') {

                // $getAllCategorys = Category::withTrashed()->get();
                
                $getAllCategorys = Category::all();

                $categories = $getAllCategorys->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->trashed()
                            ? $category->name . ' (Deletado)'
                            : $category->name,
                        'description' => $category->description,
                        'created_at' => $this->category->getFormattedDate($category, 'created_at'),
                        'updated_at' => $this->category->getFormattedDate($category, 'updated_at'),
                    ];
                });

                if ($categories) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Categorias recuperadas com sucesso.',
                        'data' => $categories,
                    ]);
                }
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
            $level = $user->level;

            if ($level == 'user') {
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
        DB::beginTransaction();

        try {
            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user') {
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

            $categoryExists = Category::where('name', $name)->first();

            if ($categoryExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um setor cadastrado com o nome informado, tente novamente com outro nome.',
                ]);
            }

            $createCategory = $this->category->create([
                'name' => $name,
                'description' => $description,
            ]);

            if ($createCategory) {

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Adicionou',
                    'table_name' => 'category',
                    'record_id' => $createCategory->id,
                    'description' => 'Adicionou uma categoria.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Categoria criada com sucesso.',
                    'data' => $createCategory,
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user') {
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

            $originalData = $category->getOriginal();

            $validatedData = $request->validate(
                $this->category->rulesCategory(),
                $this->category->feedbackCategory(),
            );

            $category->fill($validatedData);
            $category->save();

            // Verificando a mudanças e criando a string de log
            $changes = $category->getChanges(); // Retorna apenas os campos que foram alterados
            $logDescription = '';

            foreach ($changes as $key => $newValue) {
                $oldValue = $originalData[$key] ?? 'N/A'; // Valor antigo
                $logDescription .= "{$key}: {$oldValue} -> {$newValue} .";
            }

            if ($logDescription == null) {
                $logDescription = 'Nenhum.';
            }

            SystemLog::create([
                'fk_user_id' => $idUser,
                'action' => 'Atualizou',
                'table_name' => 'category',
                'record_id' => $id,
                'description' => 'Atualizou uma categoria. Dados alterados: ' . $logDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            if ($category) {
                return response()->json([
                    'success' => true,
                    'message' => 'Categoria atualizada com sucesso.',
                    'data' => $category,
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

    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();
            $level = $user->level;
            $idUser = $user->id;

            if ($level == 'user') {
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

            $deleteCategory->delete();

            if ($deleteCategory) {
                $data = DB::table('category_user')->where('fk_category_id', $id)->get();

                if (!$data == null) {
                    DB::table('category_user')->where('fk_category_id', $id)->delete();
                }

                SystemLog::create([
                    'fk_user_id' => $idUser,
                    'action' => 'Excluiu',
                    'table_name' => 'category',
                    'record_id' => $id,
                    'description' => 'Excluiu uma categoria',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Categoria removida com sucesso.',
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
}
