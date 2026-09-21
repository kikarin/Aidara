<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OtpMailService
{
    /**
     * Kirim OTP via Mail facade — format disamakan dengan tinker yang terbukti terkirim.
     */
    public function send(string $email, string $otpCode, string $context = 'otp'): void
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name', config('app.name', 'Aidara'));

        // Subject/body sederhana seperti email "Test Aidara" agar tidak difilter Gmail.
        $subject = "Aidara OTP - {$otpCode}";
        $body = "Kode OTP Aidara Anda: {$otpCode}\n\nBerlaku 10 menit.\nJangan bagikan kode ini kepada siapapun.";

        try {
            Mail::raw($body, function ($message) use ($email, $subject, $fromAddress, $fromName) {
                $message->to($email)
                    ->from($fromAddress, $fromName)
                    ->subject($subject);
            });
        } catch (Throwable $e) {
            Log::error("OTP email gagal dikirim ({$context})", [
                'email'  => $email,
                'mailer' => config('mail.default'),
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info("OTP email sent ({$context})", [
            'email'  => $email,
            'mailer' => config('mail.default'),
        ]);
    }
}
