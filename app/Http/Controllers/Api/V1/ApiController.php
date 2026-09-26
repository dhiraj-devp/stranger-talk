<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\SafetyController;
use App\Models\Block;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        if ($user->isBanned()) {
            return response()->json(['message' => 'Your account is suspended.'], 403);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $this->profilePayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->profilePayload($request->user())]);
    }

    public function blocks(Request $request): JsonResponse
    {
        $blocks = Block::query()->where('blocker_id', $request->user()->id)->latest()->get(['id', 'created_at']);

        return response()->json(['blocks' => $blocks]);
    }

    public function unblock(Request $request, Block $block): JsonResponse
    {
        if ($block->blocker_id !== $request->user()->id) {
            abort(403);
        }
        $block->delete();

        return response()->json(['ok' => true]);
    }

    private function profilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }
}
