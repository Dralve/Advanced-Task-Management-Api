<?php

namespace App\Services\Auth;

use App\Helpers\ApiResponseTrait;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use function Laravel\Prompts\error;

class AuthService
{

    use ApiResponseTrait;

    /**
     * Authenticate a user and return a token.
     *
     * @param array $credentials
     * @return JsonResponse
     */
    public function login(array $credentials): JsonResponse
    {
        try {
            $token = Auth::attempt($credentials);
            if (!$token){
                return $this->errorResponse(null, 'Invalid Credentials', 401);
            }
                return $this->responseWithToken($token, Auth::user());
        } catch (Exception $e){
            Log::error('Error Logging In: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * Refresh the authentication token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = Auth::refresh();
            return $this->responseWithToken($newToken, Auth::user());
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Token has expired',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Could not refresh the token',
            ], 500);
        }
    }
}
