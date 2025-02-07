<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function __construct(
        protected Transaction $transactionModel
    ) {}

    public function index()
    {
        return TransactionResource::collection(
            $this->transactionModel->with(['category', 'account'])->get()
        );
    }

    public function store(TransactionStoreUpdateRequest $request)
    {
        $transaction = $this->transactionModel->create($request->validated());
        $this->adjustAccountBalanceStore($transaction);
        return new TransactionResource($transaction->load(['category', 'account']));
    }

    public function show($id)
    {
        return new TransactionResource(
            $this->transactionModel->with(['category', 'account'])->findOrFail($id)
        );
    }

    public function update(TransactionStoreUpdateRequest $request, $id)
    {
        $transaction = $this->transactionModel->findOrFail($id);
        $originalTransaction = $transaction->replicate();
        $data = $request->validated();
        $transaction->update($data);
        $this->adjustAccountBalanceUpdate($transaction, $originalTransaction);
    
        return new TransactionResource($transaction->load(['category', 'account']));
    }

    public function destroy($id)
    {
        $transaction = $this->transactionModel->findOrFail($id);
        $this->adjustAccountBalanceDestroy($transaction);
        $transaction->delete();
    
        return response()->noContent();
    }

    private function adjustAccountBalanceStore(Transaction $transaction): void
    {
        $account = $transaction->account;
        $categoryType = $transaction->category->type;

        if ($categoryType === 'income') {
            $account->balance += $transaction->amount;
        } elseif ($categoryType === 'expense') {
            $account->balance -= $transaction->amount;
        }

        $account->save();
    }

    private function adjustAccountBalanceUpdate(Transaction $transaction, Transaction $originalTransaction)
    {
        $account = $transaction->account;
        $categoryType = $transaction->category->type;
        $originalAccount = $originalTransaction->account;
        $originalCategoryType = $originalTransaction->category->type;

        if ($originalCategoryType === 'income') {
            $originalAccount->balance -= $originalTransaction['amount'];
        } elseif ($originalCategoryType === 'expense') {
            $originalAccount->balance += $originalTransaction['amount'];
        }

        if ($categoryType === 'income') {
            $account->balance += $transaction->amount;
        } elseif ($categoryType === 'expense') {
            $account->balance -= $transaction->amount;
        }
        
        $originalAccount->save();
        $account->save();
    }
    private function adjustAccountBalanceDestroy(Transaction $transaction): void
    {
        $account = $transaction->account;
        $categoryType = $transaction->category->type;

        if ($categoryType === 'income') {
            $account->balance -= $transaction->amount;
        } elseif ($categoryType === 'expense') {
            $account->balance += $transaction->amount;
        }
        
        $account->save();
    }

}
