<?php

namespace App\Services;

use App\DTOs\Menu\MenuDTO;
use App\Enums\Status;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Collection;

class MenuService
{
    /**
     * Sidebar menus for the authenticated user, filtered by their view permissions.
     * Noxus users get the full active tree.
     */
    public function getByRoles(): Collection
    {
        if (PermissionService::isNoxusUser()) {
            return Menu::query()
                ->whereNull('parent_id')
                ->where('status', Status::ACTIVE->value)
                ->with(['children' => function ($query) {
                    $query->where('status', Status::ACTIVE->value)
                        ->orderBy('order');
                }])
                ->orderBy('order')
                ->get();
        }

        $userRoles = auth()->user()->roles->pluck('id');

        // A menu the user is allowed to see: active role, can_view on this menu.
        $viewable = function ($query) use ($userRoles) {
            $query->whereIn('role_id', $userRoles)
                ->where('can_view', true)
                ->where('roles.status', Status::ACTIVE->value);
        };

        return Menu::query()
            ->whereNull('parent_id')
            ->where('status', Status::ACTIVE->value)
            ->where(function ($query) use ($viewable) {
                // Show a top-level menu if it is a viewable leaf itself...
                $query->whereHas('roles', $viewable)
                    // ...or a container with at least one viewable child.
                    ->orWhereHas('children', function ($child) use ($viewable) {
                        $child->where('status', Status::ACTIVE->value)
                            ->whereHas('roles', $viewable);
                    });
            })
            ->with(['children' => function ($query) use ($viewable) {
                $query->where('status', Status::ACTIVE->value)
                    ->whereHas('roles', $viewable)
                    ->orderBy('order');
            }])
            ->orderBy('order')
            ->get();
    }

    /**
     * All active menus (for selects / role assignment). Noxus-exclusive menus are
     * hidden from non-noxus users.
     */
    public function getActive(): Collection
    {
        $query = Menu::query()
            ->whereNull('parent_id')
            ->where('status', Status::ACTIVE->value)
            ->with(['children' => function ($query) {
                $query->where('status', Status::ACTIVE->value);
            }])
            ->orderBy('order');

        if (!PermissionService::isNoxusUser()) {
            $query->where('exclusive_noxus', false);
        }

        return $query->get();
    }

    public function list(): Collection
    {
        return Menu::with('parent')->get();
    }

    public function find(string $id): Menu
    {
        return Menu::with([
            'parent',
            'roles' => function ($query) {
                $query->withPivot(['can_view', 'can_create', 'can_update']);
            }
        ])->findOrFail($id);
    }

    public function create(MenuDTO $dto): Menu
    {
        return Menu::create($dto->toArray());
    }

    public function update(string $id, MenuDTO $dto): Menu
    {
        $menu = Menu::findOrFail($id);
        $menu->update($dto->toArray());

        return $menu;
    }

    public function changeStatus(string $id): Menu
    {
        $menu = Menu::findOrFail($id);
        $menu->status = $menu->status === Status::ACTIVE->value
            ? Status::INACTIVE->value
            : Status::ACTIVE->value;
        $menu->save();

        return $menu;
    }

    public function delete(string $id): void
    {
        $menu = Menu::findOrFail($id);
        $menu->roles()->detach();
        $menu->delete();
    }
}
