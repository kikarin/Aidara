<?php

namespace App\Exports;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Support\Booking\BookingJenisSewa;
use App\Support\Booking\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $rowNumber = 0;

    /**
     * @param  Builder<Booking>  $query  Query booking yang sudah difilter dan eager-load relasi.
     */
    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor',
            'Tanggal Pengajuan',
            'Penyewa',
            'Instansi',
            'No. HP',
            'Email',
            'Venue',
            'Area',
            'Jenis Sewa',
            'Jadwal Mulai',
            'Jadwal Selesai',
            'Status',
            'Status Pembayaran',
            'Total',
            'Sudah Meeting',
            'Jadwal Meeting',
            'Lokasi Meeting',
            'Catatan Admin',
        ];
    }

    /**
     * @param  Booking  $booking
     */
    public function map($booking): array
    {
        $meeting = $booking->surats->firstWhere('jenis', BookingSurat::JENIS_UNDANGAN_MEETING);

        $sudahMeeting = $booking->status === BookingStatus::MENUNGGU_MEETING || $meeting !== null;

        $this->rowNumber++;

        return [
            $this->rowNumber,
            $booking->nomor,
            $this->formatDate($booking->submitted_at ?? $booking->created_at),
            $booking->penyewaProfile?->nama ?? $booking->user?->name ?? '-',
            $booking->penyewaProfile?->instansi ?? '-',
            $booking->penyewaProfile?->no_hp ?? '-',
            $booking->user?->email ?? '-',
            $booking->venue?->name ?? '-',
            $booking->areas->pluck('name')->implode(', ') ?: 'Seluruh venue',
            BookingJenisSewa::of($booking) === BookingJenisSewa::REGULER ? 'Reguler' : 'Event',
            $this->formatDate($booking->starts_at),
            $this->formatDate($booking->ends_at),
            BookingStatus::adminLabel((string) $booking->status),
            $this->paymentLabel($booking->payments->first()?->status),
            (int) $booking->grand_total,
            $sudahMeeting ? 'Ya' : 'Tidak',
            $meeting ? $this->formatDate($meeting->meeting_at) : '-',
            $meeting?->meeting_place ?? '-',
            $booking->admin_notes ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Pengajuan Sewa';
    }

    public function columnFormats(): array
    {
        return [
            'O' => '"Rp" #,##0',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function formatDate(?\DateTimeInterface $date): string
    {
        return $date?->format('d/m/Y H:i') ?? '-';
    }

    private function paymentLabel(?string $status): string
    {
        if ($status === null) {
            return '-';
        }

        return match ($status) {
            'pending' => 'Menunggu pembayaran',
            'awaiting_verification' => 'Bukti perlu dicek',
            'paid' => 'Sudah bayar',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            'expired' => 'Kedaluwarsa',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
