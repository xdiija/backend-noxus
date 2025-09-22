<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurrentPayment;
use App\Models\Payment;
use Carbon\Carbon;

class GenerateRecurringPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-recurring-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring payments for active transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $recurrentPayments = RecurrentPayment::where('status', 1)
            ->whereDate('next_date', '<=', $today)
            ->get();

         foreach ($recurrentPayments as $recurrence) {
            $transaction = $recurrence->transaction;
            $nextDate = $this->calculateNextDate($recurrence->next_date, $recurrence->interval);

            Payment::create([
                'transaction_id'    => $transaction->id,
                'account_id'        => $recurrence->account_id,
                'payment_method_id' => $recurrence->payment_method_id,
                'payment_number'    => $this->getNextPaymentNumber($transaction->id),
                'amount'            => $recurrence->amount,
                'due_date'          => $nextDate,
                'discount'          => 0,
                'increase'          => 0,
                'status'            => 1,
            ]);

            $recurrence->next_date = $nextDate;
            $recurrence->save();
        }

        $this->info('Recurring payments generated successfully.');
    }

    private function getNextPaymentNumber(int $transactionId): int
    {
        return Payment::where('transaction_id', $transactionId)->count() + 1;
    }

    private function calculateNextDate($currentDate, $interval)
    {
        $date = Carbon::parse($currentDate);

        return match ($interval) {
            'weekly'  => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'yearly'  => $date->addYear(),
            default   => null,
        };
    }
}
