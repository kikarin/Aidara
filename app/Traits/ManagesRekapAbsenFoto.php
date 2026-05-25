<?php

namespace App\Traits;

use App\Models\RekapAbsenProgramLatihan;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait ManagesRekapAbsenFoto
{
    protected function formatFotoAbsenMedia(Media $media, bool $useMediaDiskUrl = false): array
    {
        $url = $useMediaDiskUrl
            ? $this->getFotoAbsenMediaDiskUrl($media)
            : $media->getUrl();

        return [
            'id' => $media->id,
            'url' => $url,
            'name' => $media->name,
            'lokasi' => $media->getCustomProperty('lokasi'),
            'latitude' => $media->getCustomProperty('latitude'),
            'longitude' => $media->getCustomProperty('longitude'),
            'waktu_foto' => $media->getCustomProperty('waktu_foto'),
        ];
    }

    protected function getFotoAbsenMediaDiskUrl(Media $media): string
    {
        $fullPath = $media->getPath();
        $mediaRoot = storage_path('app/media');
        $relativePath = str_replace($mediaRoot . '/', '', $fullPath);

        return Storage::disk('media')->url($relativePath);
    }

    protected function parseFotoMetadata(mixed $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($data)) {
            return null;
        }

        $metadata = [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'lokasi' => $data['lokasi'] ?? null,
            'waktu_foto' => $data['waktu_foto'] ?? null,
        ];

        if (collect($metadata)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
            return null;
        }

        return $metadata;
    }

    protected function attachFotoAbsenWithMetadata(
        RekapAbsenProgramLatihan $rekapAbsen,
        UploadedFile $foto,
        string $tanggal,
        ?array $metadata = null
    ): void {
        $mediaAdder = $rekapAbsen->addMedia($foto)
            ->usingName('Foto Absen ' . $tanggal);

        if ($metadata) {
            $mediaAdder->withCustomProperties($metadata);
        }

        $mediaAdder->toMediaCollection('foto_absen');
    }

    protected function uploadFotoAbsenFromRequest(
        RekapAbsenProgramLatihan $rekapAbsen,
        Request $request,
        string $tanggal
    ): void {
        if (!$request->hasFile('foto_absen')) {
            return;
        }

        $fotoMetadata = $request->input('foto_lokasi', []);

        foreach ($request->file('foto_absen') as $index => $foto) {
            $this->attachFotoAbsenWithMetadata(
                $rekapAbsen,
                $foto,
                $tanggal,
                $this->parseFotoMetadata($fotoMetadata[$index] ?? null)
            );
        }
    }

    protected function uploadFotoAbsenBatch(
        RekapAbsenProgramLatihan $rekapAbsen,
        array $fotoFiles,
        array $fotoMetadata,
        string $tanggal
    ): void {
        foreach ($fotoFiles as $index => $foto) {
            if (!$foto instanceof UploadedFile) {
                continue;
            }

            $this->attachFotoAbsenWithMetadata(
                $rekapAbsen,
                $foto,
                $tanggal,
                $this->parseFotoMetadata($fotoMetadata[$index] ?? null)
            );
        }
    }

    protected function uploadFileNilaiFromRequest(
        RekapAbsenProgramLatihan $rekapAbsen,
        Request $request,
        string $tanggal
    ): void {
        if (!$request->hasFile('file_nilai')) {
            return;
        }

        $this->uploadFileNilaiBatch($rekapAbsen, $request->file('file_nilai'), $tanggal);
    }

    protected function uploadFileNilaiBatch(
        RekapAbsenProgramLatihan $rekapAbsen,
        array $files,
        string $tanggal
    ): void {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $rekapAbsen->addMedia($file)
                ->usingName('File Nilai ' . $tanggal)
                ->toMediaCollection('file_nilai');
        }
    }
}
