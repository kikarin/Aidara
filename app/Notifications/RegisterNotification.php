<?php

namespace App\Notifications;

use App\Models\Notification as ModelsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegisterNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        $data                          = $notifiable;
        $notification                  = new ModelsNotification();
        $notification->notifiable_type = get_class($notifiable);
        $notification->notifiable_id   = $notifiable->id;
        $notification->data            = $data;
        $notification->save();

        return (new MailMessage())
            ->subject('Confirm your email')
            ->greeting('Confirm your email')
            ->line('Hey '.$notifiable->nama.'!')
            ->line('Your account has been created')
            ->action('Confirm Account', url('/verify-email/'.$notifiable->verification_token))
            ->line('Thank you for signing up!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
