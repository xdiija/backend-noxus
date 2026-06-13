<?php

namespace App\Http\Controllers;

use App\DTOs\Role\RoleDTO;
use App\Http\Requests\Role\StoreUpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    public function getActive()
    {
        return RoleResource::collection($this->roleService->list(true));
    }

    public function index()
    {
        return RoleResource::collection($this->roleService->list());
    }

    public function store(StoreUpdateRoleRequest $request)
    {
        return new RoleResource(
            $this->roleService->create(RoleDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        try {
            return new RoleResource($this->roleService->find($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Perfil não encontrado ou inacessível.'], 404);
        }
    }

    public function update(StoreUpdateRoleRequest $request, string $id)
    {
        return new RoleResource(
            $this->roleService->update($id, RoleDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        return new RoleResource($this->roleService->changeStatus($id));
    }

    public function destroy(string $id)
    {
        $this->roleService->delete($id);

        return response()->noContent();
    }
}
