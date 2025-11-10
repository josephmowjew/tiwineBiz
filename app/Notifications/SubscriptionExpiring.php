<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user preferences
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', 'subscription_expiring')
            ->where('enabled', true)
            ->pluck('channel')
            ->toArray();

        // Add channels based on user preferences
        if (in_array('mail', $preferences)) {
            $channels[] = 'mail';
        }

        if (in_array('sms', $preferences)) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->subscription->end_date);

        return (new MailMessage)
            ->subject('Subscription Expiring Soon')
            ->line('Your subscription is expiring soon.')
            ->line('Plan: '.$this->subscription->plan_name)
            ->line('End Date: '.$this->subscription->end_date->format('Y-m-d'))
            ->line('Days Remaining: '.$daysRemaining)
            ->action('Renew Subscription', url('/subscriptions/'.$this->subscription->id))
            ->line('Please renew to continue enjoying our services.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $daysRemaining = now()->diffInDays($this->subscription->end_date);

        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan_name,
            'end_date' => $this->subscription->end_date->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
            'message' => "Your subscription ({$this->subscription->plan_name}) expires in {$daysRemaining} days.",
        ];
    }

    /**
     * Get the array representation for broadcast.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $daysRemaining = now()->diffInDays($this->subscription->end_date);

        return "Subscription Expiring: Your {$this->subscription->plan_name} subscription expires in {$daysRemaining} days. Please renew to continue.";
    }
}
