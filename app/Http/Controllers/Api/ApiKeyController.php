<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:web_push,email,whatsapp,telegram,discord',
            'rate_limit' => 'nullable|integer|min:100|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $permissions = $request->input('permissions', ['web_push', 'email', 'whatsapp', 'telegram', 'discord']);

        $key = 'sk_live_' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7);

        $apiKey = ApiKey::create([
            'name' => $request->input('name'),
            'key' => $key,
            'permissions' => $permissions,
            'is_active' => true,
            'rate_limit' => $request->input('rate_limit', 1000),
        ]);

        $plainSecret = \Illuminate\Support\Str::random(20);
        $apiKey->update(['secret' => hash('sha256', $plainSecret)]);

        return response()->json([
            'success' => true,
            'message' => 'API key generated successfully',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $apiKey->key,
                'secret' => $plainSecret,
                'permissions' => $apiKey->permissions,
                'rate_limit' => $apiKey->rate_limit,
                'created_at' => $apiKey->created_at
            ],
            'warning' => 'Save the secret securely. It will not be displayed again.'
        ], 201);
    }

    public function list(): JsonResponse
    {
        $apiKeys = ApiKey::select(['id', 'name', 'key', 'is_active', 'permissions', 'rate_limit', 'last_used_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $apiKeys
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $apiKey->key,
                'is_active' => $apiKey->is_active,
                'permissions' => $apiKey->permissions,
                'rate_limit' => $apiKey->rate_limit,
                'last_used_at' => $apiKey->last_used_at,
                'created_at' => $apiKey->created_at,
                'total_notifications' => $apiKey->notificationLogs()->count(),
                'successful_notifications' => $apiKey->notificationLogs()->where('status', 'sent')->count(),
            ]
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:web_push,email,whatsapp,telegram,discord',
            'rate_limit' => 'nullable|integer|min:100|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey->update($request->only(['name', 'is_active', 'permissions', 'rate_limit']));

        return response()->json([
            'success' => true,
            'message' => 'API key updated successfully',
            'data' => $apiKey
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully'
        ]);
    }

    public function regenerateSecret(string $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }

        $newKey = 'sk_live_' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7);

        $plainSecret = \Illuminate\Support\Str::random(20);

        $apiKey->update([
            'key' => $newKey,
            'secret' => hash('sha256', $plainSecret)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API key and secret regenerated successfully',
            'data' => [
                'key' => $apiKey->key,
                'secret' => $plainSecret
            ],
            'warning' => 'Save the new key and secret securely. They will not be displayed again.'
        ]);
    }
}
