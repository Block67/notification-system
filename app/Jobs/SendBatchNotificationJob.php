<?php

// app/Jobs/SendBatchNotificationJob.php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\MultiChannelNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $userIds;
    protected $title;
    protected $message;
    protected $channels;
    protected $actionUrl;

    public function __construct(
        array $userIds,
        string $title,
        string $message,
        array $channels = ['mail', 'webpush'],
        ?string $actionUrl = null
    ) {
        $this->userIds = $userIds;
        $this->title = $title;
        $this->message = $message;
        $this->channels = $channels;
        $this->actionUrl = $actionUrl;
    }

    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new MultiChannelNotification(
                    $this->title,
                    $this->message,
                    $this->channels,
                    $this->actionUrl
                ));
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send notification to user {$user->id}: " . $e->getMessage());
                $failCount++;
            }
        }

        Log::info("Batch notification completed: {$successCount} success, {$failCount} failed");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendBatchNotificationJob failed: " . $exception->getMessage());
    }
}