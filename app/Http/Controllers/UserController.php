<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{   
    public function __construct(
        protected User $model
    ) {}
    public function index()
    {
        $perPage = request()->get('per_page', 10); 
        $filter = request()->get('filter', ''); 
        $query = $this->model->with('roles');
        
        if (!empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
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
        return new UserResource(
            $this->model->with('roles')->findOrFail($id)
        );
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
