<?php

namespace App\Services\User;

use App\Helpers\ApiResponseTrait;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{

    use ApiResponseTrait;

    /**
     * @param $perPage
     * @return mixed
     */
    public function listUsers($perPage): mixed
    {
        try {
            if (!Auth::user()->can('view-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            return User::paginate($perPage);
        } catch (Exception $e){
            Log::error('Error Listing Users: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function createUser(array $data): mixed
    {
        try {


            return User::create($data);
        } catch (Exception $e){
            Log::error('Error Creating User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param User $user
     * @return User
     * @throws Exception
     */
    public function showUser(User $user): User
    {
        try {
            if (!Auth::user()->can('view-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            return $user->load('roles');
        } catch (ModelNotFoundException $e){
            Log::error('User Not Found: ' . $e->getMessage());
            throw new Exception('User Not Found');
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return User|JsonResponse
     * @throws Exception
     */
    public function updateUser(User $user, array $data): User|JsonResponse
    {
        try {
            if (!Auth::user()->can('update-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            $user->update($data);
            return $user;
        } catch (ModelNotFoundException $e){
            Log::error('User Not Found: ' . $e->getMessage());
            throw new Exception('User Not Found');
        } catch (Exception $e){
            Log::error('Error Updating User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return User
     */
    public function changePassword(User $user, array $data): User
    {
        try {
            if (Auth::id() !== $user->id) {
                Log::error('User ID mismatch. Authenticated user ID: ' . Auth::id() . ' Target user ID: ' . $user->id);
                throw new Exception('You are not allowed to perform this action.', 403);
            }

            if (!Hash::check($data['old_password'], $user->password)) {
                Log::error('Old password does not match.');
                throw new Exception('The old password is incorrect.', 403);
            }

            $user->password = Hash::make($data['password']);

            if (!$user->save()) {
                Log::error('Failed to save updated password for user ID: ' . $user->id);
                throw new Exception('Failed to update password.', 500);
            }

            return $user;
        } catch (Exception $e) {
            Log::error('Error Changing Password: ' . $e->getMessage());
            if ($e->getCode() !== 500) {
                throw new HttpResponseException($this->errorResponse(null, $e->getMessage(), $e->getCode()));
            }

            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param User $user
     * @return void
     */
    public function deleteUser(User $user): void
    {
        try {
            if (!Auth::user()->can('delete-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            $user->delete();
        } catch (Exception $e){
            Log::error('Error Deleting User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Database\Eloquent\Builder|array|Collection|Model|Builder|JsonResponse
     */
    public function restore($id): \Illuminate\Database\Eloquent\Builder|array|Collection|Model|Builder|JsonResponse
    {
        try {
            if (!Auth::user()->can('restore-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();
            return $user;
        } catch (ModelNotFoundException $e){
            Log::error('User Not Found: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'User Not Found', 404));
        } catch (Exception $e){
            Log::error('Error Restoring User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param $perPage
     * @return LengthAwarePaginator|JsonResponse
     */
    public function trashedUsers($perPage): LengthAwarePaginator|JsonResponse
    {
        try {
            return User::onlyTrashed()->paginate($perPage);
        } catch (Exception $e){
            Log::error('Error Retrieving Trashed Users: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param $id
     * @return void
     */
    public function forceDeleteUser($id): void
    {
        try {
            if (!Auth::user()->can('delete-users')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            $user = User::withTrashed()->findOrFail($id);
            $user->forceDelete();
        } catch (ModelNotFoundException $e){
            Log::error('User Not Found: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'User Not Found', 404));
        } catch (Exception $e){
            Log::error('Error Force Delete User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return JsonResponse|User
     */
    public function assignRoleToUser(User $user, array $data): JsonResponse|User
    {
        try {
            $role = $data['roles'];
            $user->assignRole($role);
            $user->load('roles');
            return $user;
        } catch (Exception $e){
            Log::error('Error Assign Role To User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return User|JsonResponse
     */
    public function unassignRoleToUser(User $user, array $data): User|JsonResponse
    {
        try {
            if (!Auth::user()->can('assign-roles')) {
                Log::error('Unauthorized action by user ID: ' . Auth::id());
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized', 403));
            }

            $role = $data['roles'];
            $user->removeRole($role);
            return $user->load('roles');
        } catch (Exception $e){
            Log::error('Error Unassign Role To User: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }
}
