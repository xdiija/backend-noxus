<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    public function __construct(
        protected Payment $paymentModel
    ) {}

    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $query = Payment::with(['transaction.category', 'account', 'paymentMethod']);
        $this->applyPaymentFilters($query);
        return PaymentResource::collection($query->paginate($perPage));
    }

    protected function applyPaymentFilters(&$query): void
    {
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

        $query->whereBetween($dateFilterOption, [$fromDate, $toDate]);
    }
    public function show($id)
    {
        return new PaymentResource(
            $this->paymentModel->findOrFail($id)
        );
    }
}