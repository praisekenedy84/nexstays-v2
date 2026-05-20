<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return $this->respondCollection(UserResource::collection($users));
    }
}
