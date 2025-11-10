<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user preferences
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', 'low_stock')
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
            ->subject('Low Stock Alert: '.$this->product->name)
            ->line('Product "'.$this->product->name.'" is running low on stock.')
            ->line('Current Stock: '.$this->product->stock_quantity.' '.$this->product->unit)
            ->line('Reorder Level: '.$this->product->reorder_level.' '.$this->product->unit)
            ->action('View Product', url('/products/'.$this->product->id))
            ->line('Please restock this item to avoid stockouts.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'stock_quantity' => $this->product->stock_quantity,
            'reorder_level' => $this->product->reorder_level,
            'unit' => $this->product->unit,
            'message' => "Product \"{$this->product->name}\" is running low on stock ({$this->product->stock_quantity} {$this->product->unit} remaining).",
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
        return "Low Stock Alert: {$this->product->name} has only {$this->product->stock_quantity} {$this->product->unit} remaining. Reorder level: {$this->product->reorder_level} {$this->product->unit}.";
    }
}
