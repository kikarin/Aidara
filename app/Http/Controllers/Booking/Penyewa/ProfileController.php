<?php

namespace App\Http\Controllers\Booking\Penyewa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdatePenyewaProfileRequest;
use App\Http\Requests\Booking\UploadKtpRequest;
use App\Models\Booking\BookingPenyewaProfile;
use App\Services\Booking\PenyewaAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PenyewaAuthService $auth,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = BookingPenyewaProfile::query()
            ->where('user_id', $request->user()->id)
            ->with(['documents.documentType'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function update(UpdatePenyewaProfileRequest $request): JsonResponse
    {
        $profile = $this->auth->upsertProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil diperbarui',
            'data' => $profile->load('documents.documentType'),
        ]);
    }

    public function uploadKtp(UploadKtpRequest $request): JsonResponse
    {
        $doc = $this->auth->uploadKtp($request->user(), $request->file('ktp'));

        return response()->json([
            'success' => true,
            'message' => 'KTP berhasil diunggah',
            'data' => [
                'id' => $doc->id,
                'file_path' => $doc->file_path,
                'file_url' => Storage::disk('public')->url($doc->file_path),
                'original_name' => $doc->original_name,
                'document_type' => $doc->documentType,
            ],
        ], 201);
    }
}
