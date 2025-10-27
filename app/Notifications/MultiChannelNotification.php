<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class MultiChannelNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $channels;
    protected $actionUrl;

    public function __construct(
        string $title, 
        string $message, 
        array $channels = ['mail', 'webpush'], 
        ?string $actionUrl = null
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->channels = $channels;
        $this->actionUrl = $actionUrl;
    }

    public function via($notifiable): array
    {
        $enabledChannels = [];

        if (in_array('mail', $this->channels) && $notifiable->email_notifications) {
            $enabledChannels[] = 'mail';
        }


        if (in_array('webpush', $this->channels) && $notifiable->push_notifications) {
            $enabledChannels[] = WebPushChannel::class;
        }

        return $enabledChannels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('View Details', $this->actionUrl);
        }

        return $mail;
    }


    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage())
            ->title($this->title)
            ->body($this->message)
            ->icon('/icon.png');

        if ($this->actionUrl) {
            $message->action('View', 'view')
                    ->data(['url' => $this->actionUrl]);
        }

        return $message;
    }
}