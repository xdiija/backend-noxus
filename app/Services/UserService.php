<?php

namespace App\Services;

use App\DTOs\User\CreateUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\Exceptions\User\InvalidPasswordChangeException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = User::with('role');

        if (!empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
        }

        if (PermissionService::isNoxusUser()) {
            return $query->paginate($perPage);
        }

        if (PermissionService::isAdminUser()) {
            $query->where('role_id', '!=', 1);
            return $query->paginate($perPage);
        }

        if (PermissionService::isRegularUser()) {
            $query->whereNotIn('role_id', [1, 2]);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): User
    {
        $query = User::with('role');

        if (PermissionService::isNoxusUser()) {
            return $query->findOrFail($id);
        }

        if (PermissionService::isAdminUser()) {
            $query->where('role_id', '!=', 1);
            return $query->findOrFail($id);
        }

        if (PermissionService::isRegularUser()) {
            $query->whereNotIn('role_id', [1, 2]);
        }

        return $query->findOrFail($id);
    }

    public function create(CreateUserDTO $dto): User
    {
        $data = $dto->toArray();
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);

        return $user;
    }

    public function update(string $id, UpdateUserDTO $dto): User
    {
        $user = User::findOrFail($id);
        $data = $dto->toArray();

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);
        User::forgetUserPermissionsCache();

        return $user;
    }

    public function changeStatus(string $id): User
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 1 ? 2 : 1;
        $user->save();

        return $user;
    }

    public function changePassword(string $id, array $data): User
    {
        if ($id != auth()->user()->id) {
            throw new InvalidPasswordChangeException('id', 'ID diferente do usuário logado!');
        }

        $user = User::findOrFail($id);

        if (!Hash::check($data['old_password'], $user->password)) {
            throw new InvalidPasswordChangeException('old_password', 'Senha antiga incorreta!');
        }

        $user->update(['password' => bcrypt($data['new_password'])]);

        return $user;
    }

    public function delete(string $id): void
    {
        $user = User::findOrFail($id);
        $user->delete();
    }
}
