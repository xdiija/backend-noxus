<?php

namespace App\Http\Controllers;

use App\DTOs\Appointment\AppointmentDTO;
use App\Http\Requests\Appointment\StoreUpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    public function index(string $animal)
    {
        return AppointmentResource::collection(
            $this->appointmentService->list($animal)
        );
    }

    public function store(StoreUpdateAppointmentRequest $request, string $animal)
    {
        return new AppointmentResource(
            $this->appointmentService->create($animal, AppointmentDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateAppointmentRequest $request, string $animal, string $appointment)
    {
        return new AppointmentResource(
            $this->appointmentService->update($animal, $appointment, AppointmentDTO::fromRequest($request))
        );
    }

    public function destroy(string $animal, string $appointment)
    {
        $this->appointmentService->delete($animal, $appointment);

        return response()->noContent();
    }
}
