<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

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

    public function changeStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status == 1 ? 2 : 1;
        $customer->save();

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }


}
