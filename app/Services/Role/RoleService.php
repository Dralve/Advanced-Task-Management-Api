<?php

namespace App\Services\Role;

use App\Helpers\ApiResponseTrait;
use App\Models\Role;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class RoleService
{

    use ApiResponseTrait;

    /**
     * @param $perPage
     * @return mixed
     */
    public function listRoles($perPage): mixed
    {
        try {
            return Role::paginate($perPage);
        } catch (Exception $e){
            Log::error('Error Listing Roles: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function createRole(array $data): mixed
    {
        try {
            return Role::create($data);
        } catch (Exception $e){
            Log::error('Error Creating Role: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param Role $role
     * @return Role
     */
    public function showRole(Role $role): Role
    {
        try {
            return $role->load('users');
        } catch (ModelNotFoundException $e){
            Log::error('Role Not Found: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }


    public function updateRole(Role $role, array $data): Role
    {
        try {
            $role->update($data);
            return $role;
        } catch (Exception $e){
            Log::error('Error Updating Role: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param Role $role
     * @return void
     * @throws Exception
     */
    public function deleteRole(Role $role): void
    {
        try {
            $role->delete();
        } catch (Exception $e){
            Log::error('Error Deleting Role: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }
}
