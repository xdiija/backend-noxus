<?php

namespace App\Services;

use App\DTOs\User\CreateUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\User\InvalidPasswordChangeException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->visibleQuery();

        if (!empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): User
    {
        return $this->visibleQuery()->findOrFail($id);
    }

    public function create(CreateUserDTO $dto): User
    {
        $data = $dto->toArray();
        $data['password'] = bcrypt($data['password']);
        // New users belong to the same organization as their creator.
        $data['organization_id'] = auth()->user()?->organization_id;
        $user = User::create($data);

        return $user;
    }

    public function update(string $id, UpdateUserDTO $dto): User
    {
        $user = $this->visibleQuery()->findOrFail($id);
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
        if ((int) $id === (int) auth()->id()) {
            throw new BusinessRuleException('Não é possível alterar o status do próprio usuário.');
        }

        $user = $this->visibleQuery()->findOrFail($id);
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
        if ((int) $id === (int) auth()->id()) {
            throw new BusinessRuleException('Não é possível excluir o próprio usuário.');
        }

        $user = $this->visibleQuery()->findOrFail($id);
        $user->delete();
    }

    /**
     * Users the authenticated user is allowed to see/act on, filtered by role
     * visibility: Noxus sees all; Admin excludes the Noxus role; regular users
     * exclude both the Noxus and Admin roles.
     */
    private function visibleQuery(): Builder
    {
        $query = User::with(['role', 'organization']);

        // Noxus is a cross-tenant super-admin: sees users of every organization.
        if (PermissionService::isNoxusUser()) {
            return $query;
        }

        // Everyone else is confined to their own organization.
        $query->where('organization_id', auth()->user()?->organization_id);

        if (PermissionService::isAdminUser()) {
            return $query->where('role_id', '!=', Role::NOXUS_ROLE);
        }

        if (PermissionService::isRegularUser()) {
            return $query->whereNotIn('role_id', [Role::NOXUS_ROLE, Role::ADMIN_ROLE]);
        }

        return $query;
    }
}
