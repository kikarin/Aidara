<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRecaptchaConfig extends Command
{
    protected $signature   = 'recaptcha:check';
    protected $description = 'Check reCAPTCHA configuration';

    public function handle()
    {
        $siteKey   = config('services.recaptcha.site_key');
        $secretKey = config('services.recaptcha.secret_key');

        $this->info('=== reCAPTCHA Configuration Check ===');
        $this->line('');

        $this->info('Site Key: ' . ($siteKey ?: 'NOT SET'));
        $this->info('Secret Key: ' . ($secretKey ? 'SET (hidden)' : 'NOT SET'));
        $this->line('');

        if ($siteKey && $secretKey) {
            $this->info('✓ reCAPTCHA is configured correctly');
        } else {
            $this->error('✗ reCAPTCHA is not configured');
            $this->line('');
            $this->warn('Please add the following to your .env file:');
            $this->line('RECAPTCHA_SITE_KEY=your-site-key-here');
            $this->line('RECAPTCHA_SECRET_KEY=your-secret-key-here');
        }

        return 0;
    }
}
