<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSurat extends Model
{
    use Blameable;
    use SoftDeletes;

    public const JENIS_UNDANGAN_MEETING = 'undangan_meeting';

    public const JENIS_BALASAN_PERSETUJUAN = 'balasan_persetujuan';

    public const JENIS_BALASAN_PENOLAKAN = 'balasan_penolakan';

    protected $table = 'booking_surats';

    protected $fillable = [
        'booking_id',
        'jenis',
        'nomor_surat',
        'perihal',
        'isi',
        'meeting_at',
        'meeting_place',
        'dokumen',
        'penandatangan_nama',
        'penandatangan_jabatan',
        'file_path',
        'sent_email_at',
        'sent_whatsapp_at',
    ];

    protected function casts(): array
    {
        return [
            'meeting_at' => 'datetime',
            'dokumen' => 'array',
            'sent_email_at' => 'datetime',
            'sent_whatsapp_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public static function jenisList(): array
    {
        return [
            self::JENIS_UNDANGAN_MEETING,
            self::JENIS_BALASAN_PERSETUJUAN,
            self::JENIS_BALASAN_PENOLAKAN,
        ];
    }

    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            self::JENIS_UNDANGAN_MEETING => 'Undangan Meeting',
            self::JENIS_BALASAN_PERSETUJUAN => 'Balasan Persetujuan',
            self::JENIS_BALASAN_PENOLAKAN => 'Balasan Penolakan',
            default => $this->jenis,
        };
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function attachmentName(): string
    {
        $safe = preg_replace('/[\/\\\\]+/', '-', $this->nomor_surat) ?? 'surat';

        return 'Surat_'.$safe.'.pdf';
    }
}
