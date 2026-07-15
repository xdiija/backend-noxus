<?php

namespace App\Http\Controllers;

use App\DTOs\Expense\ExpenseDTO;
use App\Http\Requests\Expense\IndexExpensesRequest;
use App\Http\Requests\Expense\StoreUpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\ExpenseService;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(IndexExpensesRequest $request)
    {
        $response = ExpenseResource::collection(
            $this->expenseService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateExpenseRequest $request)
    {
        return new ExpenseResource(
            $this->expenseService->create(ExpenseDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new ExpenseResource($this->expenseService->find($id));
    }

    public function update(StoreUpdateExpenseRequest $request, string $id)
    {
        return new ExpenseResource(
            $this->expenseService->update($id, ExpenseDTO::fromRequest($request))
        );
    }

    public function togglePaid(string $id)
    {
        $this->expenseService->togglePaid($id);

        return response()->json(['message' => 'Status de pagamento atualizado com sucesso']);
    }

    public function destroy(string $id)
    {
        $this->expenseService->delete($id);

        return response()->noContent();
    }
}
