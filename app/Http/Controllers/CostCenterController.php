<?php

namespace App\Http\Controllers;

use App\Http\Requests\CostCenterStoreUpdateRequest;
use App\Http\Resources\CostCenterResource;
use App\Models\CostCenter;

class CostCenterController extends Controller
{
    public function __construct(
        protected CostCenter $costsCenterModel
    ) {}

    public function index()
    {
        return CostCenterResource::collection(
            $this->costsCenterModel->get()
        );
    }

    public function store(CostCenterStoreUpdateRequest $request)
    {
        $costsCenter = $this->costsCenterModel->create(
            $request->validated()
        );

        return new CostCenterResource($costsCenter);
    }

    public function show($id)
    {
        return new CostCenterResource(
            $this->costsCenterModel->findOrFail($id)
        );
    }

    public function update(CostCenterStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $costsCenter = $this->costsCenterModel->findOrFail($id);
        $costsCenter->update($data);

        return new CostCenterResource($costsCenter);
    }

    public function destroy($id)
    {
        $costsCenter = $this->costsCenterModel->findOrFail($id);
        $costsCenter->delete();

        return response()->noContent();
    }

    public function changeStatus(string $id)
    {   
        $costCenter = $this->costsCenterModel->findOrFail($id);
        $costCenter->status = $costCenter->status === 1 ? 2 : 1;
        $costCenter->save();
        return new CostCenterResource($costCenter);
    }
}
