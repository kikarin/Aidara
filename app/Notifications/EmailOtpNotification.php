<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification // implements ShouldQueue - Disabled untuk testing, uncomment jika queue worker sudah running
{
    // use Queueable; // Uncomment jika menggunakan queue

    protected string $otpCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
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
        return (new MailMessage())
            ->subject('Kode Verifikasi Email - ' . config('app.name'))
            ->greeting('Halo!')
            ->line('Terima kasih telah mendaftar. Gunakan kode OTP berikut untuk memverifikasi email Anda:')
            ->line('**' . $this->otpCode . '**')
            ->line('Kode ini berlaku selama 10 menit.')
            ->line('Jika Anda tidak melakukan pendaftaran ini, abaikan email ini.')
            ->salutation('Terima kasih, Tim ' . config('app.name'));
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

