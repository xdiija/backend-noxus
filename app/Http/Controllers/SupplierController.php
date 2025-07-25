<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreUpdateRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function __construct(
        protected Supplier $supplierModel
    ) {}

    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $filter = request()->get('filter', '');

        $query = $this->supplierModel->query();

        if (!empty($filter)) {
            $query->where('nome_fantasia', 'like', "%{$filter}%")
                  ->orWhere('razao_social', 'like', "%{$filter}%")
                  ->orWhere('cnpj', 'like', "%{$filter}%");
        }

        return SupplierResource::collection(
            $query->paginate($perPage)
        );
    }

    public function store(SupplierStoreUpdateRequest $request)
    {
        $supplier = $this->supplierModel->create($request->validated());
        return new SupplierResource($supplier);
    }

    public function show($id)
    {
        return new SupplierResource(
            $this->supplierModel->findOrFail($id)
        );
    }

    public function update(SupplierStoreUpdateRequest $request, $id)
    {
        $supplier = $this->supplierModel->findOrFail($id);
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy($id)
    {
        $supplier = $this->supplierModel->findOrFail($id);
        $supplier->delete();
        return response()->noContent();
    }

    public function changeStatus($id)
    {
        $supplier = $this->supplierModel->findOrFail($id);
        $supplier->status = $supplier->status == 1 ? 0 : 1;
        $supplier->save();

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}