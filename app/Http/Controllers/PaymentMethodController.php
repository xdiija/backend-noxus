<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethodStoreUpdateRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethod $paymentMethodModel
    ) {}

    public function index()
    {
        return PaymentMethodResource::collection(
            $this->paymentMethodModel->get()
        );
    }

    public function store(PaymentMethodStoreUpdateRequest $request)
    {
        $paymentMethod = $this->paymentMethodModel->create(
            $request->validated()
        );

        return new PaymentMethodResource($paymentMethod);
    }

    public function show($id)
    {
        return new PaymentMethodResource(
            $this->paymentMethodModel->findOrFail($id)
        );
    }

    public function update(PaymentMethodStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();
        $paymentMethod = $this->paymentMethodModel->findOrFail($id);
        $paymentMethod->update($data);

        return new PaymentMethodResource($paymentMethod);
    }

    public function destroy($id)
    {
        $paymentMethod = $this->paymentMethodModel->findOrFail($id);
        $paymentMethod->delete();

        return response()->noContent();
    }
}