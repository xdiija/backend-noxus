<?php

namespace App\Http\Controllers;

use App\DTOs\Supplier\SupplierDTO;
use App\Http\Requests\Supplier\IndexSuppliersRequest;
use App\Http\Requests\Supplier\StoreUpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(IndexSuppliersRequest $request)
    {
        return SupplierResource::collection(
            $this->supplierService->list(
                $request->input('per_page'), $request->input('filter')
            )
        );
    }

    public function store(StoreUpdateSupplierRequest $request)
    {
        return new SupplierResource(
            $this->supplierService->create(SupplierDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new SupplierResource($this->supplierService->find($id));
    }

    public function update(StoreUpdateSupplierRequest $request, string $id)
    {
        return new SupplierResource(
            $this->supplierService->update($id, SupplierDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        $this->supplierService->changeStatus($id);

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    public function destroy(string $id)
    {
        $this->supplierService->delete($id);

        return response()->noContent();
    }
}
