<?php

namespace App\Http\Controllers;

use App\Http\Resources\DonationTypeResource;
use App\Services\DonationTypeService;

class DonationTypeController extends Controller
{
    public function __construct(
        protected DonationTypeService $donationTypeService
    ) {}

    public function index()
    {
        return DonationTypeResource::collection($this->donationTypeService->list());
    }
}
