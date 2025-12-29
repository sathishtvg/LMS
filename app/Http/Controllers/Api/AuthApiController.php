<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
    /**
     * Mobile/API login (email or phone) -> returns Sanctum token.
     * Expect: identifier (email/phone), password, optional tenant_code
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'tenant_code' => 'nullable|string',
            'identifier'  => 'required|string',
            'password'    => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        // Determine login field
        $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (!Auth::attempt([$field => $data['identifier'], 'password' => $data['password'], 'status' => 'active'])) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $user = $request->user();

        // Optional: store tenant_code in token abilities later (keep simple for now)
        $device = $data['device_name'] ?? 'android';

        // Clear old tokens for same device (optional)
        // $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role ?? null,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        return response()->json(['ok' => true]);
    }
}
