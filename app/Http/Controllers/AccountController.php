<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountStoreUpdateRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;

class AccountController extends Controller
{
    public function __construct(
        protected Account $accountModel
    ){}

    public function index()
    {
        return AccountResource::collection(
            $this->accountModel->get()
        );
    }

    public function store(AccountStoreUpdateRequest $request)
    {
        $account = $this->accountModel->create(
            $request->validated()
        );
        return new AccountResource($account);
    }

    public function show($id)
    {
        return new AccountResource(
            $this->accountModel->findOrFail($id)
        );
    }

    public function update(AccountStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $account = $this->accountModel->findOrFail($id);
        $account->update($data);

        return new AccountResource($account);
    }

    public function destroy($id)
    {
        $account = $this->accountModel->findOrFail($id);
        $account->delete();
        return response()->noContent();
    }
}
