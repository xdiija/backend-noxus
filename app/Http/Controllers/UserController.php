<?php

namespace App\Http\Controllers;

use App\DTOs\User\CreateUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\Exceptions\User\InvalidPasswordChangeException;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\IndexUsersRequest;
use App\Http\Requests\User\StoreUpdateUserRequestRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Info(title="Documentação Noxus API", version="1.0")
 */
class UserController extends Controller
{   
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(IndexUsersRequest $request)
    {
        return UserResource::collection(
            $this->userService->list(
                $request->input('per_page'), $request->input('filter')
            )
        );
    }

    public function store(StoreUpdateUserRequestRequest $request)
    {        
        return new UserResource(
            $this->userService->create(CreateUserDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        try {
            return new UserResource($this->userService->find($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuário não encontrado ou inacessível.'], 404);
        }
    }

    public function update(StoreUpdateUserRequestRequest $request, string $id)
    {
        return new UserResource(
            $this->userService->update($id, UpdateUserDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        return new UserResource($this->userService->changeStatus($id));
    }

    public function changePassword(ChangePasswordRequest $request, string $id)
    {
        try {
            return new UserResource(
                $this->userService->changePassword($id, $request->validated())
            );
        } catch (InvalidPasswordChangeException $e) {
            return response()->json([
                'errors' => [$e->field => $e->getMessage()]
            ], 400);
        }
    }

    public function destroy(string $id)
    {
        $this->userService->delete($id);

        return response()->noContent();
    }
}
