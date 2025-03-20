<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Info(
 *     title="API de Usuários",
 *     version="1.0.0",
 *     description="API para gerenciamento de usuários, incluindo criação, leitura, atualização e exclusão."
 * )
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="status", type="integer", description="1 para ativo, 2 para inativo"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="UserStoreUpdateRequest",
 *     type="object",
 *     required={"name", "email", "password", "roles"},
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="password", type="string"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="integer"))
 * )
 */
class UserController extends Controller
{
    public function __construct(
        protected User $model,
        protected PermissionService $permissionService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Lista todos os usuários",
     *     tags={"Usuários"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Número de itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filtro por nome",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/UserResource")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Cria um novo usuário",
     *     tags={"Usuários"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserStoreUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */

    public function store(UserStoreUpdateRequest $request)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        $user = User::create($data);
        $user->roles()->sync($request->roles);
        return new UserResource($user);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Retorna os detalhes de um usuário",
     *     tags={"Usuários"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do usuário",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     )
     * )
     */

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

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Atualiza um usuário existente",
     *     tags={"Usuários"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserStoreUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */

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

    /**
     * @OA\Patch(
     *     path="/api/users/{id}/change-status",
     *     summary="Altera o status de um usuário",
     *     tags={"Usuários"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do usuário alterado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     )
     * )
     */

    public function changeStatus(string $id)
    {
        $user = $this->model->findOrFail($id);
        $user->status = $user->status === 1 ? 2 : 1;
        $user->save();
        return new UserResource($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Exclui um usuário",
     *     tags={"Usuários"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Usuário excluído com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     )
     * )
     */

    public function destroy(string $id)
    {
        $user = $this->model->findOrFail($id);
        $user->roles()->detach();
        $user->delete();
        return response()->noContent();
    }
}
