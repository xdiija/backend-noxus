<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionCategoryStoreUpdateRequest;
use App\Http\Resources\TransactionCategoryResource;
use App\Models\TransactionCategory;

class TransactionCategoryController extends Controller
{
    public function __construct(
        protected TransactionCategory $transactionCategoryModel
    ){}

    public function index()
    {
        $type = request()->get('type', '');
        $query = $this->transactionCategoryModel->query();

        if (!empty($type) && $type != 'all') {
            $query->where('type', $type);
        }

        return TransactionCategoryResource::collection($query->get());
    }

    public function store(TransactionCategoryStoreUpdateRequest $request)
    {
        $transactionCategory = $this->transactionCategoryModel->create(
            $request->validated()
        );
        return new TransactionCategoryResource($transactionCategory);
    }

    public function show($id)
    {
        return new TransactionCategoryResource(
            $this->transactionCategoryModel->findOrFail($id)
        );
    }

    public function update(TransactionCategoryStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $transactionCategory = $this->transactionCategoryModel->findOrFail($id);
        $transactionCategory->update($data);

        return new TransactionCategoryResource($transactionCategory);
    }

    public function destroy($id)
    {
        $transactionCategory = $this->transactionCategoryModel->findOrFail($id);

        if ($transactionCategory->children()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma categoria que possui subcategorias.'
            ], 400);
        }

        $transactionCategory->delete();
        return response()->noContent();
    }

    public function changeStatus(string $id)
    {   
        $transactionCategory = $this->transactionCategoryModel->findOrFail($id);
        $transactionCategory->status = $transactionCategory->status === 1 ? 2 : 1;
        $transactionCategory->save();
        return new TransactionCategoryResource($transactionCategory);
    }
}