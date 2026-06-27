<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * If no organization_id is provided, a new Organization is created
     * (first admin signs up and bootstraps the tenant). Otherwise the
     * user joins an existing org (e.g. an admin invites more members).
     *
     * Only the first user of a new org can be admin. Members joining an
     * existing org default to 'customer' unless explicitly set by an admin.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Resolve or create organization
        if (isset($data['organization_id'])) {
            $organization = Organization::findOrFail($data['organization_id']);
            $role = $data['role'] ?? 'customer';
        } else {
            // New org — first user is admin
            $organization = Organization::create([
                'name' => $data['name'] . "'s Organization",
                'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            ]);
            $role = 'admin';
        }

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Authenticate user and issue a Sanctum plain access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
