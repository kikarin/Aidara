<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingSurat;
use App\Services\Booking\SuratService;

class SuratSharedController extends Controller
{
    public function __construct(
        private readonly SuratService $surats,
    ) {}

    public function __invoke(BookingSurat $surat)
    {
        return $this->surats->downloadResponse($surat);
    }
}
