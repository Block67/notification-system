<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ]
        ]);
    }

    public function subscribe(array $subscriptionData): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            ['endpoint' => $subscriptionData['endpoint']],
            [
                'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
                'auth_token' => $subscriptionData['keys']['auth'] ?? null,
                'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
                'metadata' => $subscriptionData['metadata'] ?? []
            ]
        );
    }

    public function unsubscribe(string $endpoint): bool
    {
        return PushSubscription::where('endpoint', $endpoint)->delete() > 0;
    }

    public function send(string $endpoint, array $payload): array
    {
        try {
            $subscription = PushSubscription::where('endpoint', $endpoint)->firstOrFail();

            $pushSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]);

            $notification = [
                'title' => $payload['title'] ?? 'Notification',
                'body' => $payload['body'] ?? '',
                'icon' => $payload['icon'] ?? null,
                'badge' => $payload['badge'] ?? null,
                'data' => $payload['data'] ?? [],
            ];

            $result = $this->webPush->sendOneNotification(
                $pushSubscription,
                json_encode($notification)
            );

            if ($result->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'Push notification sent successfully'
                ];
            }

            // Handle expired subscription
            if ($result->isSubscriptionExpired()) {
                $subscription->delete();
                return [
                    'success' => false,
                    'message' => 'Subscription expired and removed'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send push notification: ' . $result->getReason()
            ];

        } catch (\Exception $e) {
            Log::error('WebPush error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function sendBulk(array $endpoints, array $payload): array
    {
        $results = [];
        
        foreach ($endpoints as $endpoint) {
            $results[$endpoint] = $this->send($endpoint, $payload);
        }

        return $results;
    }
}