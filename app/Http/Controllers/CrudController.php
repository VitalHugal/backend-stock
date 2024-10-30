<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class CrudController extends Controller
{
    protected $model;

    /**
     * Constructor to inject the model dependency.
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = $this->model->all();

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (QueryException $query) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $query->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->model->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Criado com sucesso.',
                'data' => $data,
            ], Response::HTTP_CREATED);
        } catch (QueryException $query) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $query->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = $this->model->find($id);

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado para o ID informado.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (QueryException $query) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $query->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $this->model->find($id);

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado para o ID informado.',
                ], Response::HTTP_NOT_FOUND);
            }

            $data->fill($request->all());
            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Atualizado com sucesso.',
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (QueryException $query) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $query->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $data = $this->model->find($id);

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado para o ID informado.',
                ], Response::HTTP_NOT_FOUND);
            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Excluído com sucesso.'
            ], Response::HTTP_OK);
        } catch (QueryException $query) {
            return response()->json([
                'success' => false,
                'message' => 'Erro DB: ' . $query->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle exceptions.
     */
    protected function handleException(\Exception $e)
    {
        return response()->json([
            'error' => 'Database error',
            'message' => 'Erro: ' . $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}