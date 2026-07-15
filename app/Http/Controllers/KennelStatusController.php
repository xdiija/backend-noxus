<?php

namespace App\Http\Controllers;

use App\Http\Resources\KennelStatusResource;
use App\Services\KennelStatusService;

class KennelStatusController extends Controller
{
    public function __construct(
        protected KennelStatusService $kennelStatusService
    ) {}

    public function index()
    {
        return KennelStatusResource::collection($this->kennelStatusService->list());
    }
}
