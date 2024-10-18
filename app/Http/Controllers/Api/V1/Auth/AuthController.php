<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('auth:api')->except('login');
    }

    public function login(LoginRequest $request):JsonResponse
    {
        $credentials = $request->validated();
        return $this->authService->login($credentials);
    }

    public function refresh(): JsonResponse
    {
        return $this->authService->refresh();
    }

    public function logout(): JsonResponse
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'User has been logged out',
        ]);
    }

    public function current(): JsonResponse
    {
        return response()->json(Auth::user());
    }
}
