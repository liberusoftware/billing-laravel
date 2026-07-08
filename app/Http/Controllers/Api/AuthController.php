<?php

namespace App\Http\Controllers\Api;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required',
            ]
        );

        $user = User::where(
            'email',
            $request->email
        )->first();

        if (! $user || ! Hash::check(
            $request->password,
            $user->password
        )) {
            throw ValidationException::withMessages(
                [
                    'email' => ['The provided credentials are incorrect.'],
                ]
            );
        }

        // Cap requestable abilities to the authenticated user's role allowance.
        $allowed = TokenAbility::allowedFor($user);

        $validated = $request->validate(
            [
                'abilities' => 'sometimes|array',
                'abilities.*' => ['string', Rule::in($allowed)],
            ]
        );

        $abilities = $validated['abilities'] ?? $allowed;

        $token = $user->createToken($request->device_name, $abilities);

        return response()->json(
            [
                'token' => $token->plainTextToken,
                'abilities' => $abilities,
                'user' => $user,
            ]
        );
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revoked successfully']);
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked successfully']);
    }
}
