<?php

namespace App\Http\Controllers;

use App\Helpers\StatusHelper;
use App\Http\Requests\MenuStoreUpdateRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;

class MenuController extends Controller
{   
    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel
    ){}

    public function getByRoles()
    {   
        $userRoles = auth()->user()->roles->pluck('id');

        if (!PermissionService::isNoxusUser()) {
            $menus = $this->menuModel
                ->whereHas('roles', function ($query) use ($userRoles) {
                    $query
                        ->whereIn('role_id', $userRoles)
                        ->where('can_view', true)
                        ->where('status', StatusHelper::ACTIVE);
                })
                ->where('parent_id', null)
                ->where('status', StatusHelper::ACTIVE) 
                ->with(['children' => function ($query) use ($userRoles) {
                    $query
                        ->where('status', StatusHelper::ACTIVE)
                        ->whereHas('roles', function ($query) use ($userRoles) {
                            $query->whereIn('role_id', $userRoles)
                            ->where('can_view', true)
                            ->where('status', StatusHelper::ACTIVE)
                                ->orderBy('order');
                        });
                }])
                ->orderBy('order')->get();

        } else {

            $menus = $this->menuModel
                ->where('parent_id', null)
                ->where('status', StatusHelper::ACTIVE) 
                ->with(['children' => function ($query) {
                    $query->where('status', StatusHelper::ACTIVE)
                        ->orderBy('order');
                }])
                ->orderBy('order')->get();
        }



        return MenuResource::collection($menus);
    }

    public function getActive()
    {   
        $userRoles = auth()->user()->roles->pluck('id');
        $menusQuery = $this->menuModel
            ->where('parent_id', null)
            ->where('status', StatusHelper::ACTIVE) 
            ->with(['children' => function ($query) {
                $query->where('status', StatusHelper::ACTIVE);
            }])
            ->orderBy('order');
        
        if (!PermissionService::isNoxusUser()) {
            $menusQuery->where('exclusive_noxus', false);
        }
    
        return MenuResource::collection($menusQuery->get());
    }
    
    public function index()
    {   
        return MenuResource::collection($this->menuModel->with('parent')->get());
    }

    public function store(MenuStoreUpdateRequest $request)
    {
        $data = $request->validated();
        $menu = $this->menuModel->create([
            'name' => $data['name'],
            'route' => $data['route'] ?? null,
            'icon' => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'exclusive_noxus' => $data['exclusive_noxus'],
            'order' => $data['order'] ?? 0,
            'status' => $data['status'] ?? StatusHelper::ACTIVE
        ]);
        return $menu;
    }

    public function show(string $id)
    {
        $menu = $this->menuModel->with([
            'parent',
            'roles' => function ($query) {
                $query->withPivot(['can_view', 'can_create', 'can_update']);
            }
        ])->findOrFail($id);

        return new MenuResource($menu);
    }

    public function update(MenuStoreUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $menu = $this->menuModel->findOrFail($id);
        $menu->update([
            'name' => $data['name'],
            'route' => $data['route'],
            'icon' => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'exclusive_noxus' => $data['exclusive_noxus'],
            'order' => $data['order'] ?? 1,
            'status' => $data['status']
        ]);

        return new MenuResource($menu);
    }

    public function changeStatus(string $id)
    {   
        $menu = $this->menuModel->findOrFail($id);
        $menu->status = $menu->status === 1 ? 2 : 1;
        $menu->save();
        return new MenuResource($menu);
    }

    public function destroy(string $id)
    {
        $menu = $this->menuModel->findOrFail($id);
        $menu->roles()->detach();
        $menu->delete();
        return response()->noContent();
    }
}
