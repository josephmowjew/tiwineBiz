<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get SMS message from notification
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        // Get recipient phone number
        $phoneNumber = $this->getPhoneNumber($notifiable);

        if (! $phoneNumber) {
            Log::warning('No phone number found for SMS notification', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);

            return;
        }

        // Send SMS via configured provider
        $this->sendSms($phoneNumber, $message);
    }

    /**
     * Get phone number from notifiable.
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        // Try multiple phone number attributes
        return $notifiable->phone
            ?? $notifiable->phone_number
            ?? $notifiable->mobile
            ?? null;
    }

    /**
     * Send SMS message via configured provider.
     */
    protected function sendSms(string $phoneNumber, string $message): void
    {
        $provider = config('services.sms.provider', 'log');

        try {
            match ($provider) {
                'twilio' => $this->sendViaTwilio($phoneNumber, $message),
                'africastalking' => $this->sendViaAfricasTalking($phoneNumber, $message),
                default => $this->logSms($phoneNumber, $message),
            };
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendViaTwilio(string $phoneNumber, string $message): void
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.from');

        Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $phoneNumber,
                'Body' => $message,
            ]);

        Log::info('SMS sent via Twilio', [
            'to' => $phoneNumber,
            'length' => strlen($message),
        ]);
    }

    /**
     * Send SMS via Africa's Talking.
     */
    protected function sendViaAfricasTalking(string $phoneNumber, string $message): void
    {
        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');
        $from = config('services.africastalking.from');

        Http::withHeaders([
            'apiKey' => $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $username,
            'to' => $phoneNumber,
            'message' => $message,
            'from' => $from,
        ]);

        Log::info('SMS sent via Africa\'s Talking', [
            'to' => $phoneNumber,
            'length' => strlen($message),
        ]);
    }

    /**
     * Log SMS instead of sending (for development/testing).
     */
    protected function logSms(string $phoneNumber, string $message): void
    {
        Log::info('SMS notification (not sent - log mode)', [
            'to' => $phoneNumber,
            'message' => $message,
        ]);
    }
}
