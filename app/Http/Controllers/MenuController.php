<?php

namespace App\Http\Controllers;

use App\DTOs\Menu\MenuDTO;
use App\Http\Requests\Menu\StoreUpdateMenuRequest;
use App\Http\Resources\MenuResource;
use App\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(
        protected MenuService $menuService
    ) {}

    public function getByRoles()
    {
        return MenuResource::collection($this->menuService->getByRoles());
    }

    public function getActive()
    {
        return MenuResource::collection($this->menuService->getActive());
    }

    public function index()
    {
        return MenuResource::collection($this->menuService->list());
    }

    public function store(StoreUpdateMenuRequest $request)
    {
        return new MenuResource(
            $this->menuService->create(MenuDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new MenuResource($this->menuService->find($id));
    }

    public function update(StoreUpdateMenuRequest $request, string $id)
    {
        return new MenuResource(
            $this->menuService->update($id, MenuDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        return new MenuResource($this->menuService->changeStatus($id));
    }

    public function destroy(string $id)
    {
        $this->menuService->delete($id);

        return response()->noContent();
    }
}
