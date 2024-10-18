<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignRoleToUser;
use App\Http\Requests\User\ChangePassword;
use App\Http\Requests\User\UnassignRoleToUser;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponseTrait;

    protected UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        $this->middleware('auth:api');
        $this->middleware('permission:view-users');
        $this->middleware('role:admin')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page');
        $user = $this->userService->listUsers($perPage);
        return $this->resourcePaginated(UserResource::collection($user));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->createUser($data);
        return $this->successResponse(new UserResource($user), 'User Created Successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->showUser($user);
        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->updateUser($user, $data);
        return $this->successResponse(new UserResource($user), 'User Updated Successfully', 200);
    }

    /**
     * @param ChangePassword $request
     * @param User $user
     * @return JsonResponse
     */
    public function changePassword(ChangePassword $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->changePassword($user, $data);
        return $this->successResponse(new UserResource($user), 'Password Changed Successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);
        return $this->successResponse(null, 'User Deleted Successfully', 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function trashedUsers(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page');
        $users = $this->userService->trashedUsers($perPage);
        return $this->resourcePaginated(UserResource::collection($users));
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function restore($id): JsonResponse
    {
        $user = $this->userService->restore($id);
        return $this->successResponse(new UserResource($user), 'User Restored Successfully', 200);
    }

    public function forceDelete($id): JsonResponse
    {
        $this->userService->forceDeleteUser($id);
        return $this->successResponse(null, 'User Deleted Successfully', 200);
    }

    /**
     * @param AssignRoleToUser $request
     * @param User $user
     * @return JsonResponse
     */
    public function assignRoleToUser(AssignRoleToUser $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->assignRoleToUser($user, $data);
        return $this->successResponse(new UserResource($user), 'Assigned Role To User Successfully');
    }

    /**
     * @param UnassignRoleToUser $request
     * @param User $user
     * @return JsonResponse
     */
    public function unassignRoleToUser(UnassignRoleToUser $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->unassignRoleToUser($user, $data);
        return $this->successResponse(new UserResource($user), 'Unassign Role To User Successfully', 200);
    }
}
