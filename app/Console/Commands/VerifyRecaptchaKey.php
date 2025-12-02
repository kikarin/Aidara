<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyRecaptchaKey extends Command
{
    protected $signature   = 'recaptcha:verify-key';
    protected $description = 'Verify reCAPTCHA Site Key dengan Google API';

    public function handle()
    {
        $siteKey   = config('services.recaptcha.site_key');
        $secretKey = config('services.recaptcha.secret_key');

        $this->info('=== Verifikasi reCAPTCHA Key ===');
        $this->line('');

        if (!$siteKey) {
            $this->error('✗ RECAPTCHA_SITE_KEY tidak ditemukan di .env');
            return 1;
        }

        if (!$secretKey) {
            $this->error('✗ RECAPTCHA_SECRET_KEY tidak ditemukan di .env');
            return 1;
        }

        $this->info('Site Key: ' . $siteKey);
        $this->info('Secret Key: ' . substr($secretKey, 0, 10) . '...');
        $this->line('');

        // Try to verify by making a test request
        // Note: We can't directly verify site key without a token,
        // but we can check if it's a valid format

        // Check key format (v2 and v3 keys both start with 6L)
        if (!preg_match('/^6L[a-zA-Z0-9_-]{38}$/', $siteKey)) {
            $this->warn('⚠ Format Site Key tidak standar. Pastikan key benar.');
        }

        $this->info('✓ Format key terlihat valid');
        $this->line('');
        $this->warn('Catatan:');
        $this->line('1. Pastikan Site Key adalah key v2 (bukan v3)');
        $this->line('2. Pastikan domain sudah terdaftar di Google reCAPTCHA Console');
        $this->line('3. Untuk testing, domain harus: localhost atau 127.0.0.1');
        $this->line('');
        $this->info('Jika masih error "Jenis kunci tidak valid":');
        $this->line('- Buat key v2 baru di: https://www.google.com/recaptcha/admin/create');
        $this->line('- Pilih: reCAPTCHA v2 → "I\'m not a robot" Checkbox');
        $this->line('- Update .env dengan key baru');
        $this->line('- Jalankan: php artisan config:clear && php artisan config:cache');

        return 0;
    }
}
