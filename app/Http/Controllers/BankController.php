<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankStoreUpdateRequest;
use App\Http\Resources\BankResource;
use App\Models\Bank;

class BankController extends Controller
{
    public function __construct(
        protected Bank $bankModel
    ){}

    public function index()
    {
        return BankResource::collection(
            $this->bankModel->get()
        );
    }

    public function store(BankStoreUpdateRequest $request)
    {
        $bank = $this->bankModel->create(
            $request->validated()
        );
        return new BankResource($bank);
    }

    public function show($id)
    {
        return new BankResource(
            $this->bankModel->findOrFail($id)
        );
    }

    public function update(BankStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $bank = $this->bankModel->findOrFail($id);
        $bank->update($data);

        return new BankResource($bank);
    }

    public function destroy($id)
    {
        $bank = $this->bankModel->findOrFail($id);
        $bank->delete();
        return response()->noContent();
    }
}
