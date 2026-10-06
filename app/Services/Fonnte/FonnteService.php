<?php

namespace App\Services\Fonnte;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class FonnteService
{
    private const SEND_ENDPOINT = '/send';

    public function isConfigured(): bool
    {
        return (bool) config('fonnte.enabled') && filled(config('fonnte.token'));
    }

    /**
     * Send a WhatsApp message (optionally with a media/document attachment).
     *
     * @param  array<string, mixed>  $options  Extra Fonnte payload (url, filename, delay, ...).
     * @return array<string, mixed>
     */
    public function send(string $target, string $message, array $options = []): array
    {
        $this->assertConfigured();

        $target = $this->normalizePhone($target);

        if ($target === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        $payload = array_merge([
            'target'  => $target,
            'message' => $message,
        ], $options);

        $response = $this->client()->asForm()->post(
            $this->endpoint(),
            $payload
        );

        return $this->handleResponse($response, $target);
    }

    /**
     * Send a WhatsApp message with a document uploaded directly to Fonnte.
     *
     * @param  array<string, mixed>  $options  Extra Fonnte payload (delay, ...).
     * @return array<string, mixed>
     */
    public function sendDocument(string $target, string $message, string $content, string $filename, array $options = []): array
    {
        $this->assertConfigured();

        $target = $this->normalizePhone($target);

        if ($target === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException('Berkas lampiran kosong.');
        }

        $payload = array_merge([
            'target'   => $target,
            'message'  => $message,
            'filename' => $filename,
        ], $options);

        $response = $this->client()
            ->attach('file', $content, $filename)
            ->post($this->endpoint(), $payload);

        return $this->handleResponse($response, $target);
    }

    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Fonnte belum dikonfigurasi. Atur FONNTE_ENABLED dan FONNTE_TOKEN.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function handleResponse(Response $response, string $target): array
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];

        if ($response->failed() || ($data['status'] ?? false) !== true) {
            $detail = $data['detail'] ?? $data['reason'] ?? $response->body();

            Log::error('Fonnte send failed', [
                'target' => $target,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new RuntimeException('Fonnte gagal mengirim pesan: '.trim((string) $detail));
        }

        return $data;
    }

    private function client(): PendingRequest
    {
        $client = Http::withHeaders([
            'Authorization' => (string) config('fonnte.token'),
        ])->timeout((int) config('fonnte.timeout', 15));

        if (! config('fonnte.verify_ssl', true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function endpoint(): string
    {
        return rtrim((string) config('fonnte.base_url', 'https://api.fonnte.com'), '/').self::SEND_ENDPOINT;
    }
}
