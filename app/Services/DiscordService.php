<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.discord.webhook_url');
    }

    /**
     * Send a single message to Discord via webhook
     *
     * @param string $content
     * @param array $payload Optional extra payload like embeds
     * @return array
     */
    public function send(string $content, array $payload = []): array
    {
        try {
            $data = array_merge(['content' => $content], $payload);

            $response = Http::post($this->webhookUrl, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Discord message sent successfully',
                    'response' => $response->body()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send Discord message: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Discord error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a message to multiple recipients (if using multiple webhooks)
     *
     * @param array $webhooks
     * @param string $content
     * @param array $payload
     * @return array
     */
    public function sendBulk(array $webhooks, string $content, array $payload = []): array
    {
        $results = [];

        foreach ($webhooks as $url) {
            $this->webhookUrl = $url;
            $results[$url] = $this->send($content, $payload);
        }

        return $results;
    }
}
