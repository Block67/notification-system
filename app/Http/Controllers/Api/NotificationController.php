<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\ApiKey;
use App\Models\NotificationLog;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function sendWebPush(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'icon' => 'nullable|url',
            'badge' => 'nullable|url',
            'data' => 'nullable|array',
            'async' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $async = $request->input('async', true);

        $payload = [
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'icon' => $request->input('icon'),
            'badge' => $request->input('badge'),
            'data' => $request->input('data', [])
        ];

        if ($async) {
            SendNotificationJob::dispatch(
                $apiKey->id,
                'web_push',
                $request->input('endpoint'),
                $payload
            );

            return response()->json([
                'success' => true,
                'message' => 'Web push notification queued successfully'
            ]);
        }

        $result = app(WebPushService::class)->send($request->input('endpoint'), $payload);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'from' => 'nullable|email',
            'from_name' => 'nullable|string',
            'async' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $async = $request->input('async', true);

        $payload = $request->only(['subject', 'body', 'from', 'from_name']);

        if ($async) {
            SendNotificationJob::dispatch(
                $apiKey->id,
                'email',
                $request->input('to'),
                $payload
            );

            return response()->json([
                'success' => true,
                'message' => 'Email notification queued successfully'
            ]);
        }

        $result = app(\App\Services\EmailService::class)->send($request->input('to'), $payload);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendWhatsApp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'body' => 'required_without:template|string',
            'template' => 'required_without:body|array',
            'async' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $async = $request->input('async', true);

        $payload = $request->only(['body', 'template']);

        if ($async) {
            SendNotificationJob::dispatch(
                $apiKey->id,
                'whatsapp',
                $request->input('to'),
                $payload
            );

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp notification queued successfully'
            ]);
        }

        $result = app(\App\Services\WhatsAppService::class)->send($request->input('to'), $payload);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendTelegram(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
            'body' => 'required|string',
            'async' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $async = $request->input('async', true);

        $payload = $request->only(['body']);

        if ($async) {
            SendNotificationJob::dispatch(
                $apiKey->id,
                'telegram',
                $request->input('chat_id'),
                $payload
            );

            return response()->json([
                'success' => true,
                'message' => 'Telegram notification queued successfully'
            ]);
        }

        $result = app(TelegramService::class)->send($request->input('chat_id'), $payload);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendDiscord(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|string',
            'body' => 'required|string',
            'async' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $async = $request->input('async', true);
        $payload = $request->only(['body']);

        if ($async) {
            SendNotificationJob::dispatch($apiKey->id, 'discord', $request->input('channel_id'), $payload);

            return response()->json([
                'success' => true,
                'message' => 'Discord notification queued successfully'
            ]);
        }

        $result = app(\App\Services\DiscordService::class)->send($request->input('channel_id'), $payload);

        return response()->json($result, $result['success'] ? 200 : 500);
    }


    public function bulkSend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:web_push,email,whatsapp,telegram',
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*' => 'required|string',
            'payload' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $apiKey = $request->get('api_key');
        $type = $request->input('type');
        $recipients = $request->input('recipients');
        $payload = $request->input('payload');

        foreach ($recipients as $recipient) {
            SendNotificationJob::dispatch($apiKey->id, $type, $recipient, $payload);
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('%d notifications queued successfully', count($recipients)),
            'queued_count' => count($recipients)
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription = app(WebPushService::class)->subscribe($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subscription registered successfully',
            'subscription_id' => $subscription->id
        ], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $deleted = app(WebPushService::class)->unsubscribe($request->input('endpoint'));

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Unsubscribed successfully' : 'Subscription not found'
        ], $deleted ? 200 : 404);
    }

    public function getLogs(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key');

        $logs = NotificationLog::where('api_key_id', $apiKey->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key');

        $stats = [
            'total' => NotificationLog::where('api_key_id', $apiKey->id)->count(),
            'sent' => NotificationLog::where('api_key_id', $apiKey->id)->where('status', 'sent')->count(),
            'failed' => NotificationLog::where('api_key_id', $apiKey->id)->where('status', 'failed')->count(),
            'pending' => NotificationLog::where('api_key_id', $apiKey->id)->where('status', 'pending')->count(),
            'by_type' => [
                'web_push' => NotificationLog::where('api_key_id', $apiKey->id)->where('type', 'web_push')->count(),
                'email' => NotificationLog::where('api_key_id', $apiKey->id)->where('type', 'email')->count(),
                'whatsapp' => NotificationLog::where('api_key_id', $apiKey->id)->where('type', 'whatsapp')->count(),
                'telegram' => NotificationLog::where('api_key_id', $apiKey->id)->where('type', 'telegram')->count(),
            ],
            'last_24h' => NotificationLog::where('api_key_id', $apiKey->id)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
