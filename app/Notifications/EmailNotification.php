<?php

// app/Notifications/EmailNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailNotification extends Notification
{
    use Queueable;

    protected $subject;
    protected $message;
    protected $actionUrl;
    protected $actionText;

    public function __construct(string $subject, string $message, ?string $actionUrl = null, ?string $actionText = null)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText ?? 'View Details';
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line('Thank you for using our application!');
    }
}
