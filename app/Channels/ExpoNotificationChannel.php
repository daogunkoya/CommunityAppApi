<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoNotificationChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        // Get the Expo Push Tokens for this user
        // We assume the notification model has a toExpoPush method
        if (!method_exists($notification, 'toExpoPush')) {
            return;
        }

        $messageData = $notification->toExpoPush($notifiable);

        if (!$messageData) {
            return;
        }

        $tokens = $notifiable->devices()->whereNotNull('expo_push_token')->pluck('expo_push_token')->toArray();

        // Remove duplicates and empty tokens
        $tokens = array_filter(array_unique($tokens));

        if (empty($tokens)) {
            Log::info('No Expo push tokens found for user: ' . $notifiable->id);
            return;
        }

        $payloads = [];
        foreach ($tokens as $token) {
            // Expo requires tokens to start with ExponentPushToken or ExpoPushToken
            if (!str_starts_with($token, 'ExponentPushToken') && !str_starts_with($token, 'ExpoPushToken')) {
                continue;
            }

            $payload = array_merge(['to' => $token], $messageData);
            $payloads[] = $payload;
        }

        if (empty($payloads)) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $payloads);

            if (!$response->successful()) {
                Log::error('Expo Push Notification Failed. Status: ' . $response->status() . ' Body: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Expo Push Notification Exception: ' . $e->getMessage());
        }
    }
}
