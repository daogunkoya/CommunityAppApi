<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\NotificationDispatcher;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable, NotificationDispatcher;

    protected $messageModel;
    protected $sender;
    protected $conversation;

    /**
     * Create a new notification instance.
     */
    public function __construct($messageModel, $sender, $conversation)
    {
        $this->messageModel = $messageModel;
        $this->sender = $sender;
        $this->conversation = $conversation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannelsBasedOnPreference($notifiable, 'messages');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $chatName = $this->conversation->type === 'group' ? "the {$this->conversation->name} group" : "you";

        return (new MailMessage)
            ->subject("New message from {$this->sender->first_name}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("{$this->sender->first_name} sent a message to {$chatName}:")
            ->line("\"{$this->messageModel->content}\"")
            ->action('Reply Now', url("/messages/{$this->conversation->id}"))
            ->line('Keep the conversation going on MatchGrinder!');
    }

    /**
     * Get the Expo Push representation of the notification.
     */
    public function toExpoPush(object $notifiable): array
    {
        $chatName = $this->conversation->type === 'group' ? " ({$this->conversation->name})" : "";

        return [
            'title' => "{$this->sender->first_name}{$chatName}",
            'body' => $this->messageModel->content,
            'data' => [
                'type' => 'new_message',
                'conversation_id' => $this->conversation->id,
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
        $chatName = $this->conversation->type === 'group' ? " ({$this->conversation->name})" : "";

        return [
            'title' => "{$this->sender->first_name}{$chatName}",
            'body' => $this->messageModel->content,
            'type' => 'new_message',
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->sender->id,
            'message_id' => $this->messageModel->id,
        ];
    }
}
