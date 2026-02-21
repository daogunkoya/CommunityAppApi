<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\NotificationDispatcher;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable, NotificationDispatcher;

    protected $commenter;
    protected $contextName;
    protected $contextUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($commenter, $contextName, $contextUrl)
    {
        $this->commenter = $commenter;
        $this->contextName = $contextName;
        $this->contextUrl = $contextUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannelsBasedOnPreference($notifiable, 'discussions');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->commenter->first_name} left a comment on '{$this->contextName}'")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("{$this->commenter->full_name} has just replied to your post on MatchGrinder.")
            ->action('View Comment', url($this->contextUrl))
            ->line('Tap above to read their message and join the conversation!');
    }

    /**
     * Get the Expo Push representation of the notification.
     */
    public function toExpoPush(object $notifiable): array
    {
        return [
            'title' => "New Reply",
            'body' => "{$this->commenter->first_name} commented on '{$this->contextName}'",
            'data' => [
                'type' => 'new_comment',
                'url' => $this->contextUrl,
            ],
            'sound' => 'default',
            'badge' => 1,
        ];
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "New Reply",
            'body' => "{$this->commenter->first_name} commented on '{$this->contextName}'",
            'type' => 'new_comment',
            'url' => $this->contextUrl,
            'commenter_id' => $this->commenter->id,
        ];
    }
}
