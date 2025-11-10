<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Sale $sale
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user preferences
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', 'sale_completed')
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
        return (new MailMessage)
            ->subject('Sale Completed: '.$this->sale->sale_number)
            ->line('A new sale has been completed.')
            ->line('Sale Number: '.$this->sale->sale_number)
            ->line('Total Amount: '.number_format($this->sale->total_amount, 2).' '.$this->sale->currency)
            ->line('Items Sold: '.$this->sale->items->count())
            ->action('View Sale', url('/sales/'.$this->sale->id))
            ->line('Thank you for your business!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'sale_completed',
            'sale_id' => $this->sale->id,
            'sale_number' => $this->sale->sale_number,
            'total_amount' => $this->sale->total_amount,
            'currency' => $this->sale->currency,
            'items_count' => $this->sale->items->count(),
            'message' => "Sale {$this->sale->sale_number} completed. Total: ".number_format($this->sale->total_amount, 2).' '.$this->sale->currency,
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
        return "Sale {$this->sale->sale_number} completed. Total: ".number_format($this->sale->total_amount, 2).' '.$this->sale->currency.'. '.$this->sale->items->count().' items sold.';
    }
}
