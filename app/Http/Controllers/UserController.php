<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{   
    public function __construct(
        protected User $model,
        protected PermissionService $permissionService
    ) {}
    public function index()
    {
        $perPage = request()->get('per_page', 10); 
        $filter = request()->get('filter', ''); 
        $query = $this->model->with('roles');
        
        if (!empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
        }

        if (!PermissionService::isNoxusUser() && !PermissionService::isAdminUser()) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->whereIn('roles.id', [1, 2]);
            });
        } else if (!PermissionService::isNoxusUser() && PermissionService::isAdminUser()) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('roles.id', 1);
            });
        }

        return UserResource::collection(
            $query->paginate($perPage)
        );
    }

    public function store(UserStoreUpdateRequest $request)
    {   
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        $user = User::create($data);
        $user->roles()->sync($request->roles);
        return new UserResource($user);
    }

    public function show(string $id)
    {   
        $query = $this->model->with('roles');

        if (!PermissionService::isNoxusUser() && !PermissionService::isAdminUser()) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->whereIn('roles.id', [1, 2]);
            });
        } elseif (!PermissionService::isNoxusUser() && PermissionService::isAdminUser()) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('roles.id', 1);
            });
        }

        try {
            $user = $query->findOrFail($id);
            return new UserResource($user);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuário não encontrado ou inacessível.'], 404);
        }
    }

    public function update(UserStoreUpdateRequest $request, string $id)
    {   
        $user = $this->model->findOrFail($id);
        $data = $request->validated();
        if($request->password) $data['password'] = bcrypt($request->password);
        $user->update($data);
        $user->roles()->sync($request->roles);
        User::forgetUserPermissionsCache();
        return new UserResource($user);
    }

    public function changeStatus(string $id)
    {   
        $user = $this->model->findOrFail($id);
        $user->status = $user->status === 1 ? 2 : 1;
        $user->save();
        return new UserResource($user);
    }

    public function destroy(string $id)
    {
        $user = $this->model->findOrFail($id);
        $user->roles()->detach();
        $user->delete();
        return response()->noContent();
    }
}
