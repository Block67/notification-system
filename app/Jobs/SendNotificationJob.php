<?php

namespace App\Jobs;

use App\Models\ApiKey;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Services\WebPushService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private int $apiKeyId,
        private string $type,
        private string $recipient,
        private array $payload
    ) {}

    public function handle(): void
    {
        $apiKey = ApiKey::find($this->apiKeyId);

        if (!$apiKey) {
            Log::error('API Key not found', ['api_key_id' => $this->apiKeyId]);
            return;
        }

        // Create log entry
        $log = NotificationLog::create([
            'api_key_id' => $this->apiKeyId,
            'type' => $this->type,
            'recipient' => $this->recipient,
            'status' => 'pending',
            'payload' => $this->payload
        ]);

        try {
            $result = match($this->type) {
                'web_push' => app(WebPushService::class)->send($this->recipient, $this->payload),
                'email' => app(EmailService::class)->send($this->recipient, $this->payload),
                'whatsapp' => app(WhatsAppService::class)->send($this->recipient, $this->payload),
                default => ['success' => false, 'message' => 'Invalid notification type']
            };

            if ($result['success']) {
                $log->markAsSent();
            } else {
                $log->markAsFailed($result['message']);
            }

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            Log::error('Notification job failed', [
                'type' => $this->type,
                'recipient' => $this->recipient,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Re-throw for retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job completely failed after retries', [
            'type' => $this->type,
            'recipient' => $this->recipient,
            'error' => $exception->getMessage()
        ]);
    }
}