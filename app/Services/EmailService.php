<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function send(string $to, array $payload): array
    {
        try {
            $subject = $payload['subject'] ?? 'Notification';
            $body = $payload['body'] ?? '';
            $from = $payload['from'] ?? config('mail.from.address');
            $fromName = $payload['from_name'] ?? config('mail.from.name');

            Mail::send([], [], function ($message) use ($to, $subject, $body, $from, $fromName) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from, $fromName)
                    ->html($body);
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'recipient' => $to
            ];

        } catch (\Exception $e) {
            Log::error('Email error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
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