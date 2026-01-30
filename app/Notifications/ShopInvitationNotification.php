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

    protected ?string $defaultPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $shopName, string $inviterName, string $roleName, string $acceptUrl, ?string $defaultPassword = null)
    {
        $this->shopName = $shopName;
        $this->inviterName = $inviterName;
        $this->roleName = $roleName;
        $this->acceptUrl = $acceptUrl;
        $this->defaultPassword = $defaultPassword;
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
        $mail = (new MailMessage)
            ->subject('Invitation to Join '.$this->shopName)
            ->line($this->inviterName.' has invited you to join '.$this->shopName.' on TiwineBiz.')
            ->line('You have been assigned the role: '.$this->roleName);

        if ($this->defaultPassword) {
            $mail->line('Your default password is: **'.$this->defaultPassword.'**')
                ->line('Please change your password after logging in for security.');
        }

        return $mail
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
