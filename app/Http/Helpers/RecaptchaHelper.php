<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaHelper
{
    /**
     * Verify reCAPTCHA response (supports both v2 and v3)
     */
    public static function verify(string $response, string $remoteIp = null, float $minScore = 0.5): bool
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (!$secretKey) {
            Log::warning('RecaptchaHelper: RECAPTCHA_SECRET_KEY not configured');
            return false;
        }

        if (empty($response)) {
            return false;
        }

        try {
            $url  = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret'   => $secretKey,
                'response' => $response,
            ];

            if ($remoteIp) {
                $data['remoteip'] = $remoteIp;
            }

            $httpResponse = Http::asForm()->post($url, $data);

            if (!$httpResponse->successful()) {
                Log::error('RecaptchaHelper: Failed to verify reCAPTCHA', [
                    'status' => $httpResponse->status(),
                    'body'   => $httpResponse->body(),
                ]);
                return false;
            }

            $result = $httpResponse->json();

            if (!isset($result['success']) || $result['success'] !== true) {
                Log::warning('RecaptchaHelper: reCAPTCHA verification failed', [
                    'result' => $result,
                ]);
                return false;
            }

            // For v3, check score
            if (isset($result['score'])) {
                $score = (float) $result['score'];
                if ($score < $minScore) {
                    Log::warning('RecaptchaHelper: reCAPTCHA v3 score too low', [
                        'score'     => $score,
                        'min_score' => $minScore,
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('RecaptchaHelper: Exception verifying reCAPTCHA', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
