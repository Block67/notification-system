<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Envoie un message Telegram à un utilisateur ou un chat.
     *
     * @param string|int $chatId
     * @param array $payload
     * @return array
     */
    public function send(string|int $chatId, array $payload): array
    {
        try {
            $text = $payload['text'] ?? 'Notification';
            $parseMode = $payload['parse_mode'] ?? 'HTML'; // Optional: HTML or MarkdownV2

            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Telegram message sent successfully',
                    'recipient' => $chatId,
                    'message_id' => $response->json('result.message_id')
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send Telegram message: ' . $response->body(),
                'recipient' => $chatId
            ];

        } catch (\Exception $e) {
            Log::error('Telegram error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'recipient' => $chatId
            ];
        }
    }

    /**
     * Envoie un message Telegram à plusieurs utilisateurs.
     *
     * @param array $recipients
     * @param array $payload
     * @return array
     */
    public function sendBulk(array $recipients, array $payload): array
    {
        $results = [];

        foreach ($recipients as $chatId) {
            $results[$chatId] = $this->send($chatId, $payload);
        }

        return $results;
    }
}
