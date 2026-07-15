<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\MonthlyTrendsRequest;
use App\Http\Requests\Dashboard\UpcomingAppointmentsRequest;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary()
    {
        return response()->json(['data' => $this->dashboardService->summary()]);
    }

    public function monthlyTrends(MonthlyTrendsRequest $request)
    {
        return response()->json([
            'data' => $this->dashboardService->monthlyTrends($request->input('months')),
        ]);
    }

    public function upcomingAppointments(UpcomingAppointmentsRequest $request)
    {
        return response()->json([
            'data' => $this->dashboardService->upcomingAppointments($request->input('days')),
        ]);
    }
}
