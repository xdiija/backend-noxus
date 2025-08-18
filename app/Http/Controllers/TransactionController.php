<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\PaymentResource;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\TransactionCategory;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Helpers\Money;
use App\Helpers\LogHelper;
use App\Http\Requests\PaymentsGetRequest;
use App\Http\Requests\TransactionGetRequest;
use App\Http\Requests\TransferStoreUpdateRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function __construct(
        protected Transaction $transactionModel,
        protected Account $accountModel,
        protected Payment $paymentModel
    ) {}

    public function index(TransactionGetRequest $request)
    {
        $perPage = $request->per_page ?? 10;

        $query = $this->transactionModel->with([
            'category', 'payments.account', 'payments.paymentMethod'
        ]);

        $this->applyTransactionFilters($query, $request);

        return TransactionResource::collection($query->paginate($perPage));
    }

    protected function applyTransactionFilters(&$query, $request): void
    {
        $type = $request->type;
        $paymentTypes = $request->payment_type;
        $categoryId = $request->category;
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfMonth();

        if ($type) {
            $query->whereHas('category', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        if (!empty($paymentTypes)) {
            $query->where(function ($q) use ($paymentTypes) {
                if (in_array('single', $paymentTypes)) {
                    $q->orHas('payments', '=', 1);
                }

                if (in_array('installment', $paymentTypes)) {
                    $q->orHas('payments', '>', 1);
                }

                if (in_array('recurrent', $paymentTypes)) {
                    $q->orWhere('is_recurrent', true); // Adjust as needed for your DB structure
                }
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $query->whereBetween('created_at', [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay()
        ]);
    }

    public function getPayments(PaymentsGetRequest $request)
    {
        $perPage = $request->per_page ?? 10;
        $query = Payment::with(['transaction.category', 'account', 'paymentMethod']);
        $this->applyPaymentFilters($query, $request);
        return PaymentResource::collection($query->paginate($perPage));
    }

    protected function applyPaymentFilters(&$query, $request): void
    {
        $type = $request->type ?? '';
        $accounts = $request->account ?? [];
        $status = $request->status ?? [];
        $dateFilterOption = $request->date_filter_option ?? 'due_date';
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfMonth();

        $query->whereHas('transaction.category', function ($q) use ($type) {
            $q->where('type', '<>', 'transfer');

            if (!empty($type)) {
                $q->where('type', $type);
            }
        });

        if (!empty($accounts)) {
            $query->whereIn('account_id', $accounts);
        }

        if (!empty($status)) {
            $query->where(function($q) use ($status) {
                if (in_array('paid', $status)) {
                    $q->orWhereNotNull('payment_date');
                }
                
                if (in_array('overdue', $status)) {
                    $q->orWhere(function($subQuery) {
                        $subQuery->whereNull('payment_date')
                                ->where('due_date', '<', now()->toDateString());
                    });
                }
                
                if (in_array('pending', $status)) {
                    $q->orWhere(function($subQuery) {
                        $subQuery->whereNull('payment_date')
                                ->where('due_date', '>=', now()->toDateString());
                    });
                }
            });
        }

        if($dateFilterOption != 'due_date'){
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        $query->whereBetween($dateFilterOption, [$dateFrom, $dateTo]);
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
                $paymentData['transaction_id'] = $transaction->id;
                $this->createPayment($transaction->category->type, $paymentData);
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

    public function storeTransferency(TransferStoreUpdateRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            
            $fromAccount = Account::lockForUpdate()->findOrFail($validated['from_account_id']);
            $toAccount = Account::lockForUpdate()->findOrFail($validated['to_account_id']);
            $transferCategory = TransactionCategory::where('type', 'transfer')->first();


            var_dump($transferCategory);

            $transaction = Transaction::create([
                'description' => $validated['description'] ?? "Transfer from {$fromAccount->name} to {$toAccount->name}",
                'category_id' => $transferCategory->id,
            ]);

            $this->createPayment(
                'expense',
                [
                    'transaction_id' => $transaction->id,
                    'account_id' => $fromAccount->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                    'discount' => 0,
                    'increase' => 0,
                    'due_date' => $validated['transfer_date'],
                    'payment_date' => $validated['transfer_date'],
                ]
            );

            $this->createPayment(
                'income',
                [
                    'transaction_id' => $transaction->id,
                    'account_id' => $toAccount->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                    'discount' => 0,
                    'increase' => 0,
                    'due_date' => $validated['transfer_date'],
                    'payment_date' => $validated['transfer_date']
                ]
            );
                
            DB::commit();

            return new TransactionResource(
                $transaction->load(['category', 'payments.account', 'payments.paymentMethod'])
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::logThrowable('Transferência falhou', $th);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function updateTransferency(TransferStoreUpdateRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $transaction = $this->transactionModel
                ->with('payments.account', 'payments.transaction.category')
                ->findOrFail($id);

            $transferCategory = TransactionCategory::where('type', 'transfer')->firstOrFail();

            if ($transaction->category_id !== $transferCategory->id) {
                return response()->json(['error' => 'Transação não é do tipo transferência.'], 422);
            }

            $fromAccount = Account::lockForUpdate()->findOrFail($validated['from_account_id']);
            $toAccount = Account::lockForUpdate()->findOrFail($validated['to_account_id']);

            $transaction->update([
                'description' => $validated['description'] ?? "Transfer from {$fromAccount->name} to {$toAccount->name}",
            ]);

            $oldPayments = $transaction->payments->keyBy('id')->all();

            $newPaymentData = [
                'transaction_id' => $transaction->id,
                'account_id' => $fromAccount->id,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $validated['amount'],
                'discount' => 0,
                'increase' => 0,
                'due_date' => $validated['transfer_date'],
                'payment_date' => $validated['transfer_date'],
            ];

            $this->updatePayment($transaction, $oldPayments[$validated['payment_in_id']], $newPaymentData);
            $this->updatePayment($transaction, $oldPayments[$validated['payment_out_id']], $newPaymentData);

            DB::commit();

            return new TransactionResource(
                $transaction->load(['category', 'payments.account', 'payments.paymentMethod'])
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::logThrowable('Falha na atualização da transferência', $th);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function show($id)
    {
        return new TransactionResource(
            $this->transactionModel->with(['category', 'payments.account', 'payments.paymentMethod'])->findOrFail($id)
        );
    }

    public function update(TransactionStoreUpdateRequest $request, $id)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();
            
            $transaction = $this->transactionModel->with(
                'payments.account', 'payments.transaction.category'
            )->findOrFail($id);

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
            $newPaymentData['transaction_id'] = $transaction->id;
            $this->createPayment($transaction->category->type, $newPaymentData);
        }
    }

    private function deleteRemovedPayments(Transaction $transaction, array $oldPayments, array $newPaymentsIds)
    {
        foreach ($oldPayments as $oldPayment) {
            if (!in_array($oldPayment->id, $newPaymentsIds)) {

                if (!empty($oldPayment->payment_date)) {
                    $this->revertAccountBalance($oldPayment);
                }
                
                $oldPayment->delete();
            }
        }
    }

    private function updatePayment(Transaction $transaction, Payment $existingPayment, array $newPaymentData): void
    {
        LogHelper::logInfo('updatePayment', $newPaymentData);
        $newAccount = $this->getAccountForUpdate($existingPayment, $newPaymentData['account_id']);
        
        if ($this->shouldRevertBalanceOnUpdate($existingPayment, $newPaymentData)) {
            $newPaymentData['status'] = Payment::STATUS_PENDING;
            $this->revertAccountBalance($existingPayment);
        }
        
        if ($this->shouldAdjustBalanceOnUpdate($existingPayment, $newPaymentData)) {
            $newPaymentData['status'] = Payment::STATUS_PAID;
            $newAccount->adjustBalance(
                $transaction->category->type, 
                Money::fromFloatToInt($newPaymentData['amount']),
                $existingPayment
            );
        }

        $existingPayment->update($newPaymentData);
    }

    private function shouldRevertBalanceOnUpdate(Payment $existingPayment, array $newPaymentData): bool
    {
        return $existingPayment->payment_date && (
            empty($newPaymentData['payment_date']) ||
            $existingPayment->amount != $newPaymentData['amount'] ||
            $existingPayment->account->id != $newPaymentData['account_id']
        );
    }

    private function shouldAdjustBalanceOnUpdate(Payment $existingPayment, array $newPaymentData): bool
    {
        return !empty($newPaymentData['payment_date']) && (
            empty($existingPayment->payment_date) ||
            $existingPayment->amount != $newPaymentData['amount'] ||
            $existingPayment->account->id != $newPaymentData['account_id']
        );
    }

    private function getAccountForUpdate(Payment $existingPayment, int $newPaymentAccountId): Account
    {
        return $existingPayment->account->id === $newPaymentAccountId 
            ? $existingPayment->account 
            : $this->accountModel->findOrFail($newPaymentAccountId);
    }

    private function createPayment(string $transactionType, array $paymentData): Payment
    {
        $payment = Payment::create($paymentData);

        if (!empty($payment->payment_date)) {

            $payment->status = Payment::STATUS_PAID;
            $payment->save();

            $payment->account->adjustBalance(
                $transactionType, Money::fromFloatToInt($paymentData['amount']), $payment
            );
        }

        LogHelper::logInfo('createPayment', $paymentData);

        return $payment;
    }

    private function revertAccountBalance(Payment $payment)
    {
        $transactionType = $payment->transaction->category->type ?? null;

        if (!$transactionType) {
            throw new \LogicException('Transaction category type not loaded.');
        }

        $typeReverse = $transactionType === 'income' ? 'expense' : 'income';
        $amountInCents = Money::fromFloatToInt($payment->amount);
        $payment->account->adjustBalance($typeReverse, $amountInCents, $payment, true);
    }

    public function changePaymentStatus($id)
    {
        $payment = Payment::with(['transaction.category', 'account'])->findOrFail($id);
        if($payment->status == request()->get('status')) {
            return response()->json(['message' => 'Status já está definido para o valor solicitado.'], 422);
        }

        return match (request()->get('status')) {
            Payment::STATUS_PAID => $this->setPaymentAsPaid($payment, request()->get('payment_date')),
            Payment::STATUS_PENDING => $this->setPaymentAsPending($payment),
            default => response()->json(['message' => 'Status inválido'], 422),
        };
    }

    public function setPaymentAsPaid(Payment $payment, string $paymentDate)
    {
        $validator = Validator::make(
            ['payment_date' => $paymentDate],
            [
                'payment_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            ],
            [
                'payment_date.required' => 'A data de pagamento é obrigatória.',
                'payment_date.date_format' => 'O formato da data deve ser yyyy-mm-dd.',
                'payment_date.before_or_equal' => 'A data de pagamento não pode ser no futuro.',
            ]
        );
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data de pagamento inválida.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $payment->status = Payment::STATUS_PAID;
            $payment->payment_date = $paymentDate;
            $payment->save();

            $payment->account->adjustBalance(
                $payment->transaction->category->type, Money::fromFloatToInt($payment->amount), $payment
            );         

            DB::commit();

            LogHelper::logInfo('Pagamento registrado com sucesso. ' . $payment->transaction->type);

            return response()->json(['message' => 'Pagamento registrado com sucesso.']);
        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json([
                'message' => 'Erro interno no servidor.',
                'errors' => ['exception' => [$th->getMessage()]]
            ], 400);
        }
    }

    public function setPaymentAsPending(Payment $payment)
    {
        try {
            DB::beginTransaction();

            if ($payment->payment_date) {
                $this->revertAccountBalance($payment);
            }
            
            $payment->status = Payment::STATUS_PENDING;
            $payment->payment_date = NULL;
            $payment->save();

            DB::commit();

            LogHelper::logInfo('Pagamento registrado com sucesso. ');

            return response()->json(['message' => 'Pagamento registrado com sucesso.']);
        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $transaction = $this->transactionModel
                ->with('payments.account', 'payments.transaction.category')
                ->findOrFail($id);

            foreach ($transaction->payments as $payment) {
                if (!empty($payment->payment_date)) {
                    $this->revertAccountBalance($payment);
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
