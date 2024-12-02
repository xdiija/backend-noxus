<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel){}

    public function index()
    {
        return  RoleResource::collection($this->roleModel->all());
    }
    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        return new RoleResource($this->roleModel->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        //
    }
}
