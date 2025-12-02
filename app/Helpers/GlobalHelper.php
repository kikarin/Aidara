<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('set_date')) {
    function set_date($timestamp = '', $date_format = 'l, j F Y')
    {
        if (trim($timestamp) == '') {
            $timestamp = time();
        } elseif (! ctype_digit($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        // remove S (st,nd,rd,th) there are no such things in indonesia :p
        $date_format = preg_replace('/S/', '', $date_format);
        $pattern     = [
            '/Mon[^day]/',
            '/Tue[^sday]/',
            '/Wed[^nesday]/',
            '/Thu[^rsday]/',
            '/Fri[^day]/',
            '/Sat[^urday]/',
            '/Sun[^day]/',
            '/Monday/',
            '/Tuesday/',
            '/Wednesday/',
            '/Thursday/',
            '/Friday/',
            '/Saturday/',
            '/Sunday/',
            '/Jan[^uary]/',
            '/Feb[^ruary]/',
            '/Mar[^ch]/',
            '/Apr[^il]/',
            '/May/',
            '/Jun[^e]/',
            '/Jul[^y]/',
            '/Aug[^ust]/',
            '/Sep[^tember]/',
            '/Oct[^ober]/',
            '/Nov[^ember]/',
            '/Dec[^ember]/',
            '/January/',
            '/February/',
            '/March/',
            '/April/',
            '/June/',
            '/July/',
            '/August/',
            '/September/',
            '/October/',
            '/November/',
            '/December/',
        ];
        $replace = [
            'Sen',
            'Sel',
            'Rab',
            'Kam',
            'Jum',
            'Sab',
            'Min',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Ags',
            'Sep',
            'Okt',
            'Nov',
            'Des',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        $date = date($date_format, $timestamp);
        $date = preg_replace($pattern, $replace, $date);
        $date = "{$date}";

        return $date;
    }
}

function ConvertBulan($value = '')
{
    if ($value == '01') {
        return 'Januari';
    } elseif ($value == '02') {
        return 'Februari';
    } elseif ($value == '03') {
        return 'Maret';
    } elseif ($value == '04') {
        return 'April';
    } elseif ($value == '05') {
        return 'Mei';
    } elseif ($value == '06') {
        return 'Juni';
    } elseif ($value == '07') {
        return 'Juli';
    } elseif ($value == '08') {
        return 'Agustus';
    } elseif ($value == '09') {
        return 'September';
    } elseif ($value == '10') {
        return 'Oktober';
    } elseif ($value == '11') {
        return 'November';
    } elseif ($value == '12') {
        return 'Desember';
    }
}

function ConvertRpTitik($angka)
{
    return 'Rp. '.strrev(implode('.', str_split(strrev(strval($angka)), 3)));
}

function RandomString($qty, $is_number = false)
{
    $chars = ($is_number == false) ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' : '0123456789';

    return substr(str_shuffle($chars), 0, $qty);
}

function addMonthswithdate($date, $days)
{
    $time = set_date($date, 'H:i:s');
    $date = set_date($date, 'Y-m-d');
    $date = strtotime('+'.$days.' months', strtotime($date));
    $date = date('Y-m-d', $date);
    $date = $date.' '.$time;

    return $date;
}

function str_slug($value)
{
    return Str::slug($value, '-');
}

function sisaHari($date)
{
    if (Carbon::now() <= $date) {
        $diff = Carbon::parse($date)->diffInDays();
        if ($diff == 0) {
            $diff = sisaJamMenit($date);
        } else {
            $diff .= ' Hari';
        }

        return $diff;
    } else {
        return 'Berakhir';
    }
}

function sisaJamMenit($date)
{
    if (Carbon::now() <= $date) {
        $diff    = Carbon::parse($date)->diffInMinutes();
        $hours   = floor($diff / 60); // Menghitung jam dengan membagi selisih menit dengan 60
        $minutes = $diff % 60; // Menghitung menit dengan menggunakan sisa pembagian
        if ($hours == 0) {
            return "$minutes menit";
        } else {
            return "$hours jam $minutes menit";
        }
    } else {
        return 'Berakhir';
    }
}

function InitialName($string)
{
    $string = $string;
    $words  = explode(' ', $string); // Memisahkan kata dengan spasi

    $char1 = substr($words[0], 0, 1); // Mengambil karakter pertama dari kata pertama
    $char2 = isset($words[1]) ? substr($words[1], 0, 1) : ''; // Mengambil karakter pertama dari kata kedua jika ada

    $char_pertama_dua_kata = $char1.$char2;

    return $char_pertama_dua_kata;
}

function ListPerPage()
{
    return [
        12  => 12,
        25  => 25,
        50  => 50,
        100 => 100,
    ];
}

function ListBulanBelajar()
{
    $bulanOptions = [];
    for ($i = 1; $i < 13; $i++) {
        $angka                = ($i < 10) ? '0'.$i : $i;
        $bulanOptions[$angka] = set_date(date("Y-$angka-01"), 'F');
    }

    return $bulanOptions;
}

function Ymd()
{
    $sekarang = Carbon::now();

    return $sekarang->format('Y-m-d');
}

function DateNow()
{
    return Carbon::now();
}

function DateForHuman($tanggal)
{
    $tanggalCarbon = Carbon::parse($tanggal);
    $now           = Carbon::now();

    // Mengecek selisih hari
    $diffInDays = $tanggalCarbon->diffInDays($now);

    // Jika lebih dari 3 hari, kembalikan tanggal aslinya
    if ($diffInDays > 3) {
        return set_date($tanggalCarbon, 'd F Y'); // atau format lain sesuai kebutuhan
    } else {
        // Jika tidak, gunakan diffForHumans untuk representasi yang lebih manusiawi
        return $tanggalCarbon->diffForHumans();
    }
}

if (! function_exists('saparator')) {

    function saparator($value)
    {
        return number_format($value, 0, ',', '.');
    }
}

if (! function_exists('removeSaparator')) {
    function removeSaparator($value)
    {
        // Hilangkan tanda titik (.) yang digunakan sebagai pemisah ribuan
        $value = str_replace('.', '', $value);
        // Ganti tanda koma (,) yang digunakan sebagai pemisah desimal dengan tanda titik (.)
        $value = str_replace(',', '.', $value);

        return $value;
    }
}

if (! function_exists('listTrueFalse')) {
    function listTrueFalse($type = 'Ya Tidak')
    {
        if ($type == 'Ya Tidak') {
            return [
                '1' => 'Ya',
                '0' => 'Tidak',
            ];
        } elseif ($type == 'Sudah Belum') {
            return [
                '1' => 'Sudah',
                '0' => 'Belum',
            ];
        } elseif ($type == 'Aktif Nonaktif') {
            return [
                '1' => 'Aktif',
                '0' => 'Nonaktif',
            ];
        }
    }
}

if (! function_exists('listTrueFalseBg')) {
    function listTrueFalseBg($type = 'Ya Tidak')
    {
        if ($type == 'Ya Tidak') {
            return [
                '1' => 'bg',
                '0' => 'Tidak',
            ];
        } elseif ($type == 'Sudah Belum') {
            return [
                '1' => 'Sudah',
                '0' => 'Belum',
            ];
        } elseif ($type == 'Aktif Nonaktif') {
            return [
                '1' => 'Aktif',
                '0' => 'Nonaktif',
            ];
        }
    }
}

if (! function_exists('makeResponse')) {

    function makeResponse($error = 0, $message = 'success', $data = [], $status_code = 200)
    {
        return [
            'error'       => $error,
            'message'     => $message,
            'data'        => $data,
            'status_code' => $status_code,
        ];
    }
}

function getFirstNWords($string, $wordLimit)
{
    $words = explode(' ', $string);
    if (count($words) <= $wordLimit) {
        return $string;
    }

    return implode(' ', array_slice($words, 0, $wordLimit));
}

if (! function_exists('getFileUrlAndPath')) {
    function getFileUrlAndPath($fileName, $directory, $common_direktori = '')
    {
        $filePath = "{$common_direktori}/{$directory}/{$fileName}";
        if (! empty($fileName) && Storage::disk('public')->exists($filePath)) {
            $url = route('general.akses-file', [
                'direktori' => Crypt::encrypt($directory),
                'file_name' => Crypt::encrypt($fileName),
            ]);
            $path = storage_path("app/public/$filePath");

            return compact('url', 'path');
        }

        return ['url' => null, 'path' => null];
    }
}

if (! function_exists('convertTrueFalse')) {
    function convertTrueFalse($value, $type = ['Tidak', 'Ya'], $using_badge = true)
    {
        $listWarna = [
            'bg-label-danger',
            'bg-label-primary',
        ];
        $text = $type[$value];
        if ($using_badge == false) {
            return $text;
        }
        $badge_bg = $listWarna[$value];

        return "<span class='badge $badge_bg'>$text</span>";
    }
}

if (! function_exists('listPerPageAlbum')) {
    function listPerPageAlbum()
    {
        return [
            12  => 12,
            25  => 25,
            50  => 50,
            100 => 100,
        ];
    }
}

if (! function_exists('extractLatLong')) {
    function extractLatLong($url)
    {
        // Cari pola latitude dan longitude dalam URL
        preg_match('/!3d(-?\d+\.\d+)!2d(-?\d+\.\d+)/', $url, $coords);

        if (isset($coords[1]) && isset($coords[2])) {
            $latitude  = $coords[1];
            $longitude = $coords[2];

            return ['latitude' => $latitude, 'longitude' => $longitude];
        }

        // Return null jika lat dan long tidak ditemukan
        return null;

        // Return null jika lat dan long tidak ditemukan
        return null;
    }
}

if (! function_exists('checkPermission')) {
    function checkPermission($permission_name)
    {
        $auth       = Auth::user();
        $role       = Role::find($auth->current_role_id);
        $permission = $role->hasPermissionTo($permission_name);

        return $permission;
    }
}

if (! function_exists('getRole')) {
    function getRole($id)
    {
        return Role::find($id);
    }
}

if (! function_exists('periodeTanggal')) {
    function periodeTanggal($tanggal_mulai, $tanggal_selesai)
    {
        if ($tanggal_mulai != '' and $tanggal_selesai != '') {
            if (set_date($tanggal_mulai, 'Y') == set_date($tanggal_selesai, 'Y')) {
                if (set_date($tanggal_mulai, 'm') == set_date($tanggal_selesai, 'm')) {
                    return set_date($tanggal_mulai, 'd').' - '.set_date($tanggal_selesai, 'd F Y');
                } else {
                    return set_date($tanggal_mulai, 'd F').' - '.set_date($tanggal_selesai, 'd F Y');
                }
            } else {
                return set_date($tanggal_mulai, 'd F Y').' - '.set_date($tanggal_selesai, 'd F Y');
            }
        } else {
            return null;
        }
    }
}

if (! function_exists('toDate')) {
    function toDate($date)
    {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($date))->format('Y-m-d');
    }
}

if (! function_exists('getFirstParagraph')) {
    function getFirstParagraph($input, $maxWords = 20)
    {
        // Menghapus tag HTML
        $text = strip_tags($input);

        // Memecah teks menjadi kata-kata
        $words = explode(' ', $text);

        // Mengambil maksimal $maxWords kata
        $firstWords = array_slice($words, 0, $maxWords);

        // Menggabungkan kembali kata-kata menjadi kalimat
        return implode(' ', $firstWords);
    }
}

if (! function_exists('formatFileSize')) {
    function formatFileSize($size)
    {
        if ($size >= 1048576) { // Lebih dari 1MB
            return number_format($size / 1048576, 2).' MB';
        } elseif ($size >= 1024) { // Lebih dari 1KB
            return number_format($size / 1024, 2).' KB';
        }

        return $size.' B';
    }
}

if (! function_exists('isNavbarTransparent')) {
    function isNavbarTransparent($is_style_padding = false)
    {
        $is_navbar_transparent = false;
        if ($is_style_padding == true and $is_navbar_transparent == true) {
            return 'padding-navbar-transparent';
        }

        return $is_navbar_transparent;
    }
}

if (! function_exists('getIdentity')) {
    function getIdentity($identity = null)
    {
        $identityRepository = app()->make(\App\Repositories\IdentityRepository::class);
        if ($identity != null) {
            return $identityRepository->getCache()->where('kode', $identity)->first();
        } else {
            return $identityRepository->getCache();
        }
    }
}

if (! function_exists('generateCaptcha')) {
    function generateCaptcha($name = 'captcha')
    {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);

        session([$name => $num1 + $num2]);

        return "$num1 + $num2";
    }
}

if (! function_exists('verifyCaptcha')) {
    function verifyCaptcha($name = 'captcha', $answer = '')
    {
        return session($name) == $answer;
    }
}

if (! function_exists('listBulan')) {
    function listBulan()
    {
        return [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
    }
}
