<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use RespondsWithJsonApi;

    public function login(LoginRequest $request): JsonResponse
    {
        $tenant = Tenant::find($request->validated('property_code'));

        if (! $tenant) {
            throw ValidationException::withMessages([
                'property_code' => ['Property code not found.'],
            ]);
        }

        if (! empty($tenant->suspended_at)) {
            $reason = trim((string) ($tenant->suspension_reason ?? ''));
            $message = $reason !== ''
                ? "This property is suspended: {$reason}"
                : 'This property is suspended and cannot be accessed. Please contact the developer or NexStay support.';

            throw ValidationException::withMessages([
                'property_code' => [$message],
            ]);
        }

        tenancy()->initialize($tenant);

        $user = User::findByLoginIdentifier($request->loginIdentifier());

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name', 'api'));

        return $this->respond([
            'user' => UserResource::make($user->load('roles')),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->respond(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond(
            UserResource::make($request->user()->load('roles', 'permissions'))
        );
    }
}
