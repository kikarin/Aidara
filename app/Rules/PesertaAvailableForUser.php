<?php

namespace App\Rules;

use App\Services\UserPesertaLinkService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PesertaAvailableForUser implements ValidationRule
{
    public function __construct(
        private ?string $pesertaType,
        private ?int $userId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !$this->pesertaType) {
            return;
        }

        $service    = app(UserPesertaLinkService::class);
        $modelClass = $service->modelClassForType($this->pesertaType);

        if (!$modelClass) {
            $fail('Jenis peserta tidak valid.');

            return;
        }

        $peserta = $modelClass::find($value);
        if (!$peserta) {
            $fail('Data peserta tidak ditemukan.');

            return;
        }

        if ($peserta->users_id && (int) $peserta->users_id !== (int) $this->userId) {
            $fail('Peserta ini sudah terhubung ke akun lain.');
        }
    }
}
