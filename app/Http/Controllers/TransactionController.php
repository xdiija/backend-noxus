<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\PaymentResource;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Helpers\Money;
use App\Helpers\LogHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function __construct(
        protected Transaction $transactionModel,
        protected Account $accountModel,
        protected Payment $paymentModel
    ) {}

    public function index()
    {
        return TransactionResource::collection(
            $this->transactionModel->with([
                'category', 'payments.account', 'payments.paymentMethod'
            ])->get()
        );
    }

    public function getPayments()
    {
        $perPage = request()->get('per_page', 10);
        $query = Payment::with(['transaction.category', 'account', 'paymentMethod']);
        $this->applyPaymentFilters($query);
        return PaymentResource::collection($query->paginate($perPage));
    }

    protected function applyPaymentFilters(&$query): void
    {
        $onlyOverdue = request()->get('only_overdue', false);
        $type = request()->get('type');
        $month = request()->get('month');
        $dateFilterOption = request()->get('date_filter_option', 'due_date');
        $date = $month ? Carbon::parse("{$month}-01") : now();
        $fromDate = $date->copy()->startOfMonth();
        $toDate = $date->copy()->endOfMonth();

        if ($type) {
            $query->whereHas('transaction.category', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        if ($onlyOverdue) {
            $dateFilterOption = 'due_date';
            $query->whereNull('payment_date');
        }

        $query->whereBetween($dateFilterOption, [$fromDate, $toDate]);
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
            
            $this->createPayment($transaction, $newPaymentData);
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
        
        if ($existingPayment->payment_date) {
            $this->revertAccountBalance($existingPayment);
        }
        
        $existingPayment->update($newPaymentData);
        
        if (!empty($newPaymentData['payment_date'])) {
            $newAccount->adjustBalance(
                $transaction->category->type, 
                Money::fromFloatToInt($newPaymentData['amount'])
            );
        }
    }

    private function getAccountForUpdate(Payment $existingPayment, int $newPaymentAccountId): Account
    {
        return $existingPayment->account->id === $newPaymentAccountId 
            ? $existingPayment->account 
            : $this->accountModel->findOrFail($newPaymentAccountId);
    }

    private function createPayment(Transaction $transaction, array $paymentData): void
    {
        $paymentData['transaction_id'] = $transaction->id;
        $payment = Payment::create($paymentData);

        if (!empty($payment->payment_date)) {
            $payment->account->adjustBalance(
                $transaction->category->type, Money::fromFloatToInt($paymentData['amount'])
            );
        }

        LogHelper::logInfo('createPayment', $paymentData);
    }

    private function revertAccountBalance(Payment $payment)
    {
        $transactionType = $payment->transaction->category->type ?? null;

        if (!$transactionType) {
            throw new \LogicException('Transaction category type not loaded.');
        }

        $typeReverse = $transactionType === 'income' ? 'expense' : 'income';
        $amountInCents = Money::fromFloatToInt($payment->amount);
        $payment->account->adjustBalance($typeReverse, $amountInCents);
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
                $payment->transaction->category->type, Money::fromFloatToInt($payment->amount)
            );            

            DB::commit();

            LogHelper::logInfo('Pagamento registrado com sucesso. ' . $payment->transaction->type);

            return response()->json(['message' => 'Pagamento registrado com sucesso.']);
        } catch (\Throwable $th) {
            LogHelper::logThrowable('Operation Failed', $th);
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
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
