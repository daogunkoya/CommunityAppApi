<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\NotificationDispatcher;

class GameInviteNotification extends Notification
{
    use Queueable, NotificationDispatcher;

    protected $gameEvent;
    protected $inviter;

    /**
     * Create a new notification instance.
     */
    public function __construct($gameEvent, $inviter)
    {
        $this->gameEvent = $gameEvent;
        $this->inviter = $inviter;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannelsBasedOnPreference($notifiable, 'game_invites');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Fallback checks for missing relations to prevent crashes
        $gameName = $this->gameEvent->gameType->name ?? 'a game';
        $time = optional($this->gameEvent->start_time)->format('M j, Y g:i A') ?? 'an upcoming time';

        return (new MailMessage)
            ->subject("{$this->inviter->first_name} invited you to play {$gameName}!")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("You have been invited by {$this->inviter->first_name} to join their upcoming game on MatchGrinder.")
            ->line("Game: {$gameName}")
            ->line("Time: {$time}")
            ->action('View Game Details', url("/games/{$this->gameEvent->id}"))
            ->line('Tap the button above to accept or decline the invitation!');
    }

    /**
     * Get the Expo Push representation of the notification.
     */
    public function toExpoPush(object $notifiable): array
    {
        $gameName = $this->gameEvent->gameType->name ?? 'a game';

        return [
            'title' => "New Game Invite!",
            'body' => "{$this->inviter->first_name} invited you to play {$gameName}.",
            'data' => [
                'type' => 'game_invite',
                'game_id' => $this->gameEvent->id,
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
        $gameName = $this->gameEvent->gameType->name ?? 'a game';

        return [
            'title' => "New Game Invite!",
            'body' => "{$this->inviter->first_name} invited you to play {$gameName}.",
            'type' => 'game_invite',
            'game_id' => $this->gameEvent->id,
            'inviter_id' => $this->inviter->id,
        ];
    }
}
