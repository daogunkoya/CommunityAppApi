<?php

namespace App\Traits;

use App\Channels\ExpoNotificationChannel;
use Illuminate\Support\Facades\Log;

trait NotificationDispatcher
{
    /**
     * Determine which channels the notification should be sent on based on user preferences.
     *
     * @param  mixed  $notifiable (Usually the User model)
     * @param  string $category (e.g., 'messages', 'game_invites')
     * @param  array  $defaultChannels Fallback channels if no preferences exist
     * @return array
     */
    protected function getChannelsBasedOnPreference($notifiable, string $category, array $defaultChannels = ['database', 'mail', ExpoNotificationChannel::class])
    {
        if (!method_exists($notifiable, 'notificationPreferences')) {
            return $defaultChannels;
        }

        $preference = $notifiable->notificationPreferences()->where('category', $category)->first();

        // If no explicit preference exists, use the defaults provided by the specific notification class
        if (!$preference) {
            return $defaultChannels;
        }

        // If frequency is 'never', return an empty array to mute the notification completely
        if (($preference->frequency ?? 'instant') === 'never') {
            return [];
        }

        // We ALWAYS return the database channel unless the frequency is 'never' (handled above)
        // This ensures the in-app Bell icon always populates natively.
        $channels = ['database'];

        if ($preference->channel_push) {
            $channels[] = ExpoNotificationChannel::class;
        }

        if ($preference->channel_email) {
            $channels[] = 'mail';
        }

        if ($preference->channel_sms) {
            // Note: This string can be mapped to Twilio/Vonage depending on the installed package
            Log::info("SMS channel explicitly requested for user {$notifiable->id}, but SMS driver is not yet installed.");
        }

        return $channels;
    }
}
