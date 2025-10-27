<?php

// app/Jobs/SendNotificationJob.php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\MultiChannelNotification;
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
    public $backoff = [10, 30, 60];

    protected $userId;
    protected $title;
    protected $message;
    protected $channels;
    protected $actionUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $userId,
        string $title,
        string $message,
        array $channels = ['mail', 'webpush'],
        ?string $actionUrl = null
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->channels = $channels;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::error("User not found: {$this->userId}");
                return;
            }

            $user->notify(new MultiChannelNotification(
                $this->title,
                $this->message,
                $this->channels,
                $this->actionUrl
            ));

            Log::info("Notification sent successfully to user {$this->userId}");

        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage());
            throw $e; // Relancer pour retry automatique
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendNotificationJob failed permanently for user {$this->userId}: " . $exception->getMessage());
    }
}