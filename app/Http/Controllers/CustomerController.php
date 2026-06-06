<?php

namespace App\Http\Controllers;

use App\DTOs\Customer\CustomerDTO;
use App\Http\Requests\Customer\StoreUpdateCustomerRequest;
use App\Http\Requests\Customer\IndexCustomersRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    public function index(IndexCustomersRequest $request)
    {
        return CustomerResource::collection(
            $this->customerService->list(
                $request->input('per_page'), $request->input('filter')
            )
        );
    }

    public function store(StoreUpdateCustomerRequest $request)
    {
        return new CustomerResource(
            $this->customerService->create(CustomerDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new CustomerResource($this->customerService->find($id));
    }

    public function update(StoreUpdateCustomerRequest $request, string $id)
    {
        return new CustomerResource(
            $this->customerService->update($id, CustomerDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        $this->customerService->changeStatus($id);

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    public function destroy(string $id)
    {
        $this->customerService->delete($id);

        return response()->noContent();
    }
}
