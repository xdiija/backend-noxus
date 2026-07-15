<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseCategoryResource;
use App\Services\ExpenseCategoryService;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        protected ExpenseCategoryService $expenseCategoryService
    ) {}

    public function index()
    {
        return ExpenseCategoryResource::collection($this->expenseCategoryService->list());
    }
}
