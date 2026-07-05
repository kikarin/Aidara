<?php

namespace App\Services\Chatbot;

use App\Models\UsersMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChatbotKnowledgeBase
{
    public function getMenusCacheVersion(): string
    {
        $updatedAt = UsersMenu::query()->max('updated_at');

        return (string) ($updatedAt ?? '0');
    }

    public function getModuleList(): string
    {
        $cacheKey = 'chatbot:module_list:'.$this->getMenusCacheVersion();

        return Cache::remember($cacheKey, 3600, function () {
            $menus = UsersMenu::query()
                ->where('rel', 0)
                ->orderBy('urutan')
                ->with(['children' => fn ($q) => $q->orderBy('urutan')])
                ->get();

            $lines = [];

            foreach ($menus as $menu) {
                $lines[] = "- {$menu->nama} (kode: {$menu->kode}, URL: {$menu->url})";

                foreach ($menu->children as $child) {
                    $lines[] = "  - {$child->nama} (kode: {$child->kode}, URL: {$child->url})";
                }
            }

            $lines[] = '';
            $lines[] = 'PENTING: Gunakan kolom "nama" di atas sebagai nama menu resmi di UI. Kode modul (mis. PEMERIKSAAN-KHUSUS) dipakai untuk routing meski nama tampilan berbeda.';

            return implode("\n", $lines);
        });
    }

    public function getOperationalGuide(): string
    {
        $path = base_path('docs/chatbot-cms-guide.md');

        if (! File::exists($path)) {
            return '';
        }

        return File::get($path);
    }

    public function getDocumentationContext(): string
    {
        $cacheKey = 'chatbot:docs_context:'.$this->getMenusCacheVersion();

        return Cache::remember($cacheKey, 3600, function () {
            $paths       = config('gemini.knowledge_paths', ['docs']);
            $maxPerFile  = config('gemini.knowledge_max_chars_per_file', 4000);
            $maxTotal    = config('gemini.knowledge_max_total_chars', 50000);
            $basePath    = base_path();
            $chunks      = [];
            $totalLength = 0;

            foreach ($paths as $relativePath) {
                $fullPath = $basePath.DIRECTORY_SEPARATOR.trim($relativePath, '/\\');

                if (! File::isDirectory($fullPath)) {
                    continue;
                }

                $files = File::allFiles($fullPath);

                foreach ($files as $file) {
                    if (! in_array(strtolower($file->getExtension()), ['md', 'txt'], true)) {
                        continue;
                    }

                    $content = File::get($file->getPathname());

                    if ($content === '') {
                        continue;
                    }

                    $relative = Str::after($file->getPathname(), $basePath.DIRECTORY_SEPARATOR);
                    $snippet  = Str::limit($content, $maxPerFile, "\n...[dipotong]");

                    $section = "### {$relative}\n{$snippet}";

                    if ($totalLength + strlen($section) > $maxTotal) {
                        break 2;
                    }

                    $chunks[]     = $section;
                    $totalLength += strlen($section);
                }
            }

            return implode("\n\n", $chunks);
        });
    }

    public function buildSystemPrompt(): string
    {
        $appName   = config('gemini.app_name');
        $modules   = $this->getModuleList();
        $guide     = $this->getOperationalGuide();
        $docs      = $this->getDocumentationContext();
        $rejection = config('gemini.rejection_message');

        return <<<PROMPT
Anda adalah asisten bantuan penggunaan Aplikasi {$appName} (Aidara/DISPORA).
Peran Anda: membantu pengguna memahami cara menggunakan modul-modul di Aplikasi ini.

ATURAN KETAT:
1. Jawab HANYA pertanyaan tentang cara menggunakan Aplikasi {$appName}: modul, menu, form, CRUD, permission, alur kerja, dan API internal yang didokumentasikan.
2. Jika pertanyaan di luar lingkup (cuaca, politik, coding umum, aplikasi lain, resep, hiburan, dll.), jawab HANYA dengan kalimat berikut tanpa tambahan:
   "{$rejection}"
3. Jangan mengarang fitur, field, atau endpoint yang tidak ada di dokumentasi atau daftar modul.
4. **SELALU gunakan nama menu persis dari bagian DAFTAR MODUL** (data live dari sistem). Jika dokumentasi menyebut "Pemeriksaan Khusus" tetapi daftar modul menampilkan nama lain (mis. "Pemeriksaan Kondisi Fisik/Kebugaran"), gunakan nama dari daftar modul.
5. Modul kode **PEMERIKSAAN-KHUSUS** (URL `/pemeriksaan-khusus`) ≠ modul **PEMERIKSAAN** (URL `/pemeriksaan`). Pertanyaan tes kondisi fisik/kebugaran → modul PEMERIKSAAN-KHUSUS.
6. Untuk pertanyaan lanjutan ("lalu setelah itu?", "lanjutkan", "apa saja?", klarifikasi user), lanjutkan konteks percakapan sebelumnya — jangan tolak atau mengulang pesan penolakan.
7. Format jawaban dengan Markdown: **tebal** untuk label, daftar bernomor `1.` `2.` `3.` untuk langkah berurutan (jangan putus di tengah), bullet `*` untuk sub-poin, backtick untuk field/enum.
8. Selesaikan semua poin yang diminta user. Jangan berhenti di tengah kalimat atau di tengah daftar. Jika jawaban panjang, tetap lengkap.
9. Gunakan Bahasa Indonesia yang jelas dan ramah.
10. Jika tidak yakin, katakan informasi tidak tersedia dan sarankan menu/modul terkait dari daftar modul.

DAFTAR MODUL APLIKASI (menu utama & sub-menu — SUMBER UTAMA NAMA MENU):
{$modules}

PANDUAN OPERASIONAL CMS (prioritas tinggi):
{$guide}

DOKUMENTASI INTERNAL (ringkasan teknis — nama menu bisa kedaluwarsa, ikuti DAFTAR MODUL):
{$docs}
PROMPT;
    }
}
