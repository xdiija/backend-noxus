<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Helpers\Money;
use App\Helpers\LogHelper;

class TransactionController extends Controller
{
    public function __construct(
        protected Transaction $transactionModel,
        protected Account $accountModel,
    ) {}

    public function index()
    {
        return TransactionResource::collection(
            $this->transactionModel->with([
                'category', 'payments.account', 'payments.paymentMethod'
            ])->get()
        );
    }

    public function store(TransactionStoreUpdateRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
    
            $transaction = $this->transactionModel->create([
                'description' => $data['description'],
                'category_id' => $data['category_id'],
            ]);
    
            foreach ($data['payments'] as $paymentData) {
                $this->createPayment($transaction, $paymentData);
            }
            
            DB::commit();

            LogHelper::logInfo('Transaction added successfully', $data);
    
            return new TransactionResource(
                $transaction->load(['category', 'payments.account', 'payments.paymentMethod'])
            );

        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function show($id)
    {
        return new TransactionResource(
            $this->transactionModel->with(['category', 'account'])->findOrFail($id)
        );
    }

    public function update(TransactionStoreUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $transaction = $this->transactionModel->with('payments.account', 'category')->findOrFail($id);
            $transaction->update([ 'description' => $data['description']]);

            $this->handleUpdatePayments($transaction, $data['payments']);

            DB::commit();

            LogHelper::logInfo('Transaction updated successfully', $data);

            return new TransactionResource(
                $transaction->load(['category', 'payments.account', 'payments.paymentMethod'])
            );
            
        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    private function handleUpdatePayments(Transaction $transaction, array $newPayments): void
    {
        $oldPayments = $transaction->payments->keyBy('id')->all();
        $newPaymentsIds = collect($newPayments)->pluck('id')->filter()->toArray();

        $this->deleteRemovedPayments($transaction, $oldPayments, $newPaymentsIds);

        foreach ($newPayments as $newPaymentData) {

            if (!empty($newPaymentData['id']) && isset($oldPayments[$newPaymentData['id']])) {
                $this->updatePayment($transaction, $oldPayments[$newPaymentData['id']], $newPaymentData);
                continue;
            } 

            $this->createPayment($transaction, $newPaymentData);
        }
    }

    private function deleteRemovedPayments(Transaction $transaction, array $oldPayments, array $newPaymentsIds)
    {
        foreach ($oldPayments as $oldPayment) {
            if (!in_array($oldPayment->id, $newPaymentsIds)) {

                if (!empty($oldPayment->payment_date)) {
                    $this->revertAccountBalance($oldPayment, $transaction);
                }
                
                $oldPayment->delete();
            }
        }
    }

    private function updatePayment(Transaction $transaction, Payment $existingPayment, array $newPaymentData): void
    {
        LogHelper::logInfo('updatePayment', $newPaymentData);
        $newAccount = $this->accountModel->findOrFail($newPaymentData['account_id']);
        
        if ($this->shouldRevertAccountBalace($existingPayment, $newPaymentData)) {
            LogHelper::logInfo('shouldRevertAccountBalace', []);

            $this->revertAccountBalance($existingPayment, $transaction);

            if (!empty($newPaymentData['payment_date'])) {
                LogHelper::logInfo('Adjusting Balance', $newPaymentData);
                $newAccount->adjustBalance(
                    $transaction->category->type, $newPaymentData['amount']
                );
            }

            $existingPayment->update($newPaymentData);

            return;
        }

        $existingPayment->update($newPaymentData);

        if (!empty($newPaymentData['payment_date']) && empty($existingPayment->payment_date)) {
            LogHelper::logInfo('Adjusting Balance', $newPaymentData);
            $newAccount->adjustBalance(
                $transaction->category->type, $newPaymentData['amount']
            );
        }
    }

    private function shouldRevertAccountBalace(Payment $existingPayment, array $newPaymentData)
    {
        return $existingPayment->payment_date &&
        (
            Money::fromFloatToInt($existingPayment->amount) != $newPaymentData['amount'] ||
            $existingPayment->account->id != $newPaymentData['account_id'] ||
            empty($newPaymentData['payment_date'])
        );
    }

    private function createPayment(Transaction $transaction, array $paymentData): void
    {
        $paymentData['transaction_id'] = $transaction->id;
        $payment = Payment::create($paymentData);

        if (!empty($payment->payment_date)) {
            $payment->account->adjustBalance(
                $transaction->category->type, $paymentData['amount']
            );
        }

        LogHelper::logInfo('createPayment', $paymentData);
    }

    private function revertAccountBalance(Payment $payment, Transaction $transaction)
    {
        $typeReverse = $transaction->category->type === 'income' ? 'expense' : 'income';
        $amountInCents = Money::fromFloatToInt($payment->amount);
        $payment->account->adjustBalance($typeReverse, $amountInCents);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $transaction = $this->transactionModel
                ->with('payments.account', 'category')
                ->findOrFail($id);

            foreach ($transaction->payments as $payment) {
                if (!empty($payment->payment_date)) {
                    $this->revertAccountBalance($payment, $transaction);
                }
                $payment->delete();
            }

            $transaction->delete();
            DB::commit();
            LogHelper::logInfo('Transaction deleted successfully');

            return response()->noContent();

        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
