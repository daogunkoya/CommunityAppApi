<?php

namespace App\Notifications;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\NotificationDispatcher;

class TournamentJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable, NotificationDispatcher;

    protected $tournament;

    /**
     * Create a new notification instance.
     *
     * @param Tournament $tournament
     */
    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->getChannelsBasedOnPreference($notifiable, 'tournaments', ['database', 'mail']);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $tournamentUrl = config('app.frontend_url', config('app.url')) . '/tournaments/' . $this->tournament->id;

        return (new MailMessage)
            ->subject("You're in! " . $this->tournament->name)
            ->view('emails.tournament-joined', [
                'first_name' => $notifiable->first_name,
                'tournament_name' => $this->tournament->name,
                'tournament_url' => $tournamentUrl,
                'email' => $notifiable->email,
            ]);
    }

    /**
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => "Tournament Registration Confirmed",
            'body' => "You have successfully registered for {$this->tournament->name}",
            'type' => 'tournament_joined',
            'tournament_id' => $this->tournament->id,
        ];
    }
}
