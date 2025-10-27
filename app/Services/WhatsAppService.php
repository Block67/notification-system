<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $accessToken;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    public function send(string $to, array $payload): array
    {
        try {
            // Remove + or any special characters from phone number
            $to = preg_replace('/[^0-9]/', '', $to);

            $messageData = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
            ];

            // Handle different message types
            if (isset($payload['template'])) {
                // Template message
                $messageData['type'] = 'template';
                $messageData['template'] = $payload['template'];
            } else {
                // Text message
                $messageData['type'] = 'text';
                $messageData['text'] = [
                    'body' => $payload['body'] ?? 'Notification'
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $messageData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully',
                    'recipient' => $to,
                    'message_id' => $response->json('messages.0.id')
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp message: ' . $response->body(),
                'recipient' => $to
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'recipient' => $to
            ];
        }
    }

    public function sendBulk(array $recipients, array $payload): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->send($recipient, $payload);
        }

        return $results;
    }
}