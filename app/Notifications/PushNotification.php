<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $icon;
    protected $data;
    protected $actionUrl;

    public function __construct(
        string $title, 
        string $body, 
        ?string $icon = null, 
        array $data = [],
        ?string $actionUrl = null
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->icon = $icon ?? '/icon.png';
        $this->data = $data;
        $this->actionUrl = $actionUrl;
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage())
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->badge('/badge.png')
            ->data($this->data);

        if ($this->actionUrl) {
            $message->action('View', 'view')
                    ->data(['url' => $this->actionUrl]);
        }

        return $message;
    }
}