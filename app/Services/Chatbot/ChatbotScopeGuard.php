<?php

namespace App\Services\Chatbot;

class ChatbotScopeGuard
{
    /** @var list<string> */
    private array $cmsKeywords = [
        'aidara', 'dispora', 'aplikasi', 'dashboard', 'modul', 'menu', 'permission', 'role',
        'atlet', 'pelatih', 'tenaga pendukung', 'unit pendukung', 'registrasi',
        'cabor', 'kategori', 'program latihan', 'rekap absen', 'pemeriksaan',
        'pemeriksaan khusus', 'pemeriksaan kondisi', 'kondisi fisik', 'kebugaran',
        'tes kondisi', 'tes fisik', 'input hasil tes', 'hasil tes', 'setup aspek',
        'turnamen', 'event', 'prestasi', 'data master',
        'tingkat', 'parameter', 'kecamatan', 'desa', 'kelurahan', 'dokumen',
        'sertifikat', 'users', 'login', 'logout', 'form', 'tambah', 'edit',
        'hapus', 'simpan', 'export', 'import', 'filter', 'cari', 'absen',
        'latihan', 'tes', 'hasil', 'peserta', 'approval', 'persetujuan',
        'cara', 'bagaimana', 'dimana', 'langkah', 'tutorial', 'bantuan', 'help',
        'menggunakan', 'gunakan', 'isi', 'input', 'upload', 'download',
        'api', 'endpoint', 'field', 'validasi', 'error', 'akses', 'action', 'aksi',
        'detail', 'delete', 'setup', 'lanjutkan', 'lanjut', 'setelah itu',
    ];

    /** @var list<string> */
    private array $offTopicKeywords = [
        'cuaca', 'weather', 'resep masak', 'bitcoin', 'crypto', 'saham',
        'film', 'lagu', 'musik', 'sepakbola dunia', 'politik', 'presiden',
        'puisi', 'cerpen', 'novel', 'translate ke', 'terjemahkan ke',
        'python tutorial', 'javascript tutorial', 'react native', 'laravel tutorial umum',
        'chatgpt', 'openai', 'gemini api key', 'hack', 'crack',
        'kalkulator', 'matematika soal', 'homework', 'tugas sekolah',
    ];

    /** @var list<string> */
    private array $followUpPhrases = [
        'lalu', 'setelah itu', 'selanjutnya', 'lanjutkan', 'lanjut', 'terus',
        'kemudian', 'trus', 'teruskan', 'apa saja', 'jelaskan lagi', 'maksud saya',
        'bukan itu', 'sorry', 'maaf', 'koreksi', 'yang tadi', 'sebelumnya',
        'di atas', 'tadi', 'lanjut dong', 'bisa dilanjut', 'tolong lanjutkan',
        'langkah berikutnya', 'apa lagi', 'selanjutnya apa', 'kemudian apa',
        'terus apa', 'habis itu', 'setelah klik', 'setelah itu apa',
    ];

    /** @var list<string> */
    private array $greetingPatterns = [
        'halo', 'hai', 'hi', 'hello', 'selamat', 'pagi', 'siang', 'sore', 'malam',
        'terima kasih', 'thanks', 'makasih', 'apa kabar',
    ];

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function isInScope(string $message, array $history = []): bool
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return false;
        }

        if ($this->isFollowUpInConversation($normalized, $history)) {
            return true;
        }

        foreach ($this->offTopicKeywords as $keyword) {
            if (str_contains($normalized, $this->normalize($keyword))) {
                return false;
            }
        }

        foreach ($this->cmsKeywords as $keyword) {
            if (str_contains($normalized, $this->normalize($keyword))) {
                return true;
            }
        }

        foreach ($this->greetingPatterns as $greeting) {
            if (str_contains($normalized, $greeting) && mb_strlen($normalized) < 80) {
                return true;
            }
        }

        if (preg_match('/\b(bantuan|help|apa\s+itu|jelaskan)\b/u', $normalized)) {
            return true;
        }

        return false;
    }

    public function rejectionMessage(): string
    {
        return (string) config('gemini.rejection_message');
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function isFollowUpInConversation(string $normalized, array $history): bool
    {
        if (count($history) < 2) {
            return false;
        }

        foreach ($this->followUpPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        if (mb_strlen($normalized) <= 80 && count($history) >= 2) {
            if (preg_match('/^(iya|ya|ok|oke|nggak|tidak|bukan|maksud|sorry|maaf|oh|hmm)/u', $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }
}
