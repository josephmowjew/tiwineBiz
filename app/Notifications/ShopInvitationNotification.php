<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShopInvitationNotification extends Notification
{
    use Queueable;

    protected string $shopName;

    protected string $inviterName;

    protected string $roleName;

    protected string $acceptUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $shopName, string $inviterName, string $roleName, string $acceptUrl)
    {
        $this->shopName = $shopName;
        $this->inviterName = $inviterName;
        $this->roleName = $roleName;
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation to Join '.$this->shopName)
            ->line($this->inviterName.' has invited you to join '.$this->shopName.' on TiwineBiz.')
            ->line('You have been assigned the role: '.$this->roleName)
            ->action('Accept Invitation', $this->acceptUrl)
            ->line('This invitation will expire in 7 days.')
            ->line('If you did not expect this invitation, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'shop_name' => $this->shopName,
            'inviter_name' => $this->inviterName,
            'role_name' => $this->roleName,
        ];
    }
}
