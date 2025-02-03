<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public function __construct(
        protected Customer $customerModel
    ){}

    public function index()
    {
        return CustomerResource::collection(
            $this->customerModel->get()
        );
    }

    public function store(CustomerStoreUpdateRequest $request)
    {
        $customer = $this->customerModel->create($request->validated());
        return new CustomerResource($customer);
    }

    public function show($id)
    {
        return new CustomerResource(
            Customer::findOrFail($id)
        );
    }

    public function update(CustomerStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $customer = $this->customerModel->findOrFail($id);
        $customer->update($data);

        return new CustomerResource($customer);
    }

    public function destroy($id)
    {
        $customer = $this->customerModel->findOrFail($id);
        $customer->delete();
        return response()->noContent();
    }
}
