<?php

namespace App\Notifications;

use App\Models\Credit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Credit $credit
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user preferences
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', 'payment_reminder')
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
        $daysOverdue = now()->diffInDays($this->credit->due_date, false);

        return (new MailMessage)
            ->subject('Payment Reminder: '.$this->credit->reference_number)
            ->line('This is a payment reminder for an outstanding credit.')
            ->line('Reference: '.$this->credit->reference_number)
            ->line('Amount Due: '.number_format($this->credit->amount_due, 2).' '.$this->credit->currency)
            ->line('Due Date: '.$this->credit->due_date->format('Y-m-d'))
            ->lineIf($daysOverdue > 0, 'Days Overdue: '.$daysOverdue)
            ->action('View Credit', url('/credits/'.$this->credit->id))
            ->line('Please make payment at your earliest convenience.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $daysOverdue = now()->diffInDays($this->credit->due_date, false);

        return [
            'type' => 'payment_reminder',
            'credit_id' => $this->credit->id,
            'reference_number' => $this->credit->reference_number,
            'amount_due' => $this->credit->amount_due,
            'currency' => $this->credit->currency,
            'due_date' => $this->credit->due_date->format('Y-m-d'),
            'days_overdue' => $daysOverdue > 0 ? $daysOverdue : 0,
            'message' => "Payment reminder: {$this->credit->reference_number}. Amount due: ".number_format($this->credit->amount_due, 2).' '.$this->credit->currency,
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
        $daysOverdue = now()->diffInDays($this->credit->due_date, false);
        $message = "Payment Reminder: {$this->credit->reference_number}. Amount: ".number_format($this->credit->amount_due, 2).' '.$this->credit->currency;

        if ($daysOverdue > 0) {
            $message .= ". {$daysOverdue} days overdue.";
        }

        return $message;
    }
}
