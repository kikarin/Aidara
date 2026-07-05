<?php

namespace App\Services;

use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserPesertaLinkService
{
    public const PESERTA_ROLE_IDS = [35, 36, 37];

    public const ROLE_TO_PESERTA_TYPE = [
        35 => 'atlet',
        36 => 'pelatih',
        37 => 'tenaga_pendukung',
    ];

    public const PESERTA_TYPE_LABELS = [
        'atlet'            => 'Atlet',
        'pelatih'          => 'Pelatih',
        'tenaga_pendukung' => 'Tenaga Pendukung',
    ];

    public const PESERTA_TYPE_TO_MODEL = [
        'atlet'            => Atlet::class,
        'pelatih'          => Pelatih::class,
        'tenaga_pendukung' => TenagaPendukung::class,
    ];

    public const PESERTA_TYPE_TO_EDIT_ROUTE = [
        'atlet'            => 'atlet.edit',
        'pelatih'          => 'pelatih.edit',
        'tenaga_pendukung' => 'tenaga-pendukung.edit',
    ];

    public function requiresPesertaLink(array $roleIds): bool
    {
        $primaryRoleId = (int) ($roleIds[0] ?? 0);

        return in_array($primaryRoleId, self::PESERTA_ROLE_IDS, true);
    }

    public function pesertaTypeFromRole(int $roleId): ?string
    {
        return self::ROLE_TO_PESERTA_TYPE[$roleId] ?? null;
    }

    public function modelClassForType(string $pesertaType): ?string
    {
        return self::PESERTA_TYPE_TO_MODEL[$pesertaType] ?? null;
    }

    /**
     * Resolve peserta model for a user (single source of truth).
     * Auto-repairs missing users.peserta_type / peserta_id when relation exists.
     */
    public function resolvePeserta(User $user, bool $autoSync = true): ?Model
    {
        $user->loadMissing(['atlet', 'pelatih', 'tenagaPendukung']);

        $pesertaType = $user->peserta_type ?: $this->pesertaTypeFromRole((int) $user->current_role_id);
        $peserta     = null;

        if ($pesertaType) {
            $modelClass = $this->modelClassForType($pesertaType);
            if ($modelClass) {
                $peserta = $modelClass::where('users_id', $user->id)->first();

                if (!$peserta && $user->peserta_id) {
                    $peserta = $modelClass::find($user->peserta_id);
                }
            }
        }

        if (!$peserta) {
            $peserta = match ((int) $user->current_role_id) {
                35      => $user->atlet,
                36      => $user->pelatih,
                37      => $user->tenagaPendukung,
                default => null,
            };

            if ($peserta && !$pesertaType) {
                $pesertaType = $this->pesertaTypeFromRole((int) $user->current_role_id);
            }
        }

        if ($peserta && $autoSync) {
            $this->syncUserColumnsFromPeserta($user, $pesertaType, $peserta);
        }

        return $peserta;
    }

    /**
     * Link user ↔ peserta (bidirectional, transactional).
     */
    public function link(User $user, string $pesertaType, int $pesertaId): void
    {
        $modelClass = $this->modelClassForType($pesertaType);
        if (!$modelClass) {
            throw new InvalidArgumentException("Invalid peserta type: {$pesertaType}");
        }

        DB::transaction(function () use ($user, $pesertaType, $pesertaId, $modelClass) {
            /** @var Model|null $peserta */
            $peserta = $modelClass::find($pesertaId);
            if (!$peserta) {
                throw new InvalidArgumentException('Data peserta tidak ditemukan.');
            }

            if ($peserta->users_id && (int) $peserta->users_id !== (int) $user->id) {
                throw new InvalidArgumentException('Peserta sudah terhubung ke akun lain.');
            }

            $this->clearPesertaLinkFromUser($user, $pesertaId, $pesertaType);

            $user->update([
                'peserta_type' => $pesertaType,
                'peserta_id'   => $pesertaId,
            ]);

            $peserta->update(['users_id' => $user->id]);

            Log::info('UserPesertaLinkService: linked', [
                'user_id'      => $user->id,
                'peserta_type' => $pesertaType,
                'peserta_id'   => $pesertaId,
            ]);
        });
    }

    /**
     * Remove user ↔ peserta link.
     */
    public function unlink(User $user): void
    {
        DB::transaction(function () use ($user) {
            foreach (self::PESERTA_TYPE_TO_MODEL as $type => $modelClass) {
                $modelClass::where('users_id', $user->id)->update(['users_id' => null]);
            }

            $user->update([
                'peserta_type' => null,
                'peserta_id'   => null,
            ]);

            Log::info('UserPesertaLinkService: unlinked', ['user_id' => $user->id]);
        });
    }

    /**
     * Sync users.peserta_* from an existing peserta relation (repair legacy data).
     */
    public function syncUserColumnsFromPeserta(User $user, ?string $pesertaType, Model $peserta): void
    {
        if (!$pesertaType) {
            return;
        }

        if ((int) $user->peserta_id === (int) $peserta->id
            && $user->peserta_type === $pesertaType
            && (int) $peserta->users_id === (int) $user->id) {
            return;
        }

        $user->update([
            'peserta_type' => $pesertaType,
            'peserta_id'   => $peserta->id,
        ]);

        if ((int) $peserta->users_id !== (int) $user->id) {
            $peserta->update(['users_id' => $user->id]);
        }
    }

    /**
     * Format peserta info for Users show/edit UI.
     */
    public function formatPesertaInfo(User $user): ?array
    {
        $peserta = $this->resolvePeserta($user);
        if (!$peserta) {
            if (!$user->peserta_type && !$user->peserta_id) {
                return null;
            }

            return [
                'peserta_type'        => $user->peserta_type,
                'peserta_type_label'  => self::PESERTA_TYPE_LABELS[$user->peserta_type] ?? $user->peserta_type,
                'peserta_id'          => $user->peserta_id,
                'peserta_nama'        => null,
                'peserta_nik'         => null,
                'peserta_is_active'   => null,
                'edit_url'            => null,
                'registration_status' => $user->registration_status,
                'is_orphan'           => true,
            ];
        }

        $pesertaType = $user->peserta_type ?: $this->pesertaTypeFromRole((int) $user->current_role_id);
        $editRoute   = self::PESERTA_TYPE_TO_EDIT_ROUTE[$pesertaType] ?? null;

        return [
            'peserta_type'        => $pesertaType,
            'peserta_type_label'  => self::PESERTA_TYPE_LABELS[$pesertaType] ?? $pesertaType,
            'peserta_id'          => $peserta->id,
            'peserta_nama'        => $peserta->nama ?? null,
            'peserta_nik'         => $peserta->nik ?? null,
            'peserta_is_active'   => (bool) ($peserta->is_active ?? false),
            'edit_url'            => $editRoute ? route($editRoute, $peserta->id) : null,
            'registration_status' => $user->registration_status,
            'is_orphan'           => false,
        ];
    }

    /**
     * Options for peserta picker (admin Users form).
     */
    public function getPesertaOptions(string $pesertaType, ?int $forUserId = null, ?string $search = null, int $limit = 50): array
    {
        $modelClass = $this->modelClassForType($pesertaType);
        if (!$modelClass) {
            return [];
        }

        $query = $modelClass::query()
            ->select(['id', 'nama', 'nik', 'email', 'users_id', 'is_active'])
            ->where(function ($q) use ($forUserId) {
                $q->whereNull('users_id');
                if ($forUserId) {
                    $q->orWhere('users_id', $forUserId);
                }
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nama')->limit($limit)->get()->map(function ($row) {
            $label = $row->nama ?: "ID #{$row->id}";
            if ($row->nik) {
                $label .= " · NIK {$row->nik}";
            }
            if ($row->users_id) {
                $label .= ' (terhubung)';
            }

            return [
                'value'      => $row->id,
                'label'      => $label,
                'nama'       => $row->nama,
                'nik'        => $row->nik,
                'email'      => $row->email,
                'users_id'   => $row->users_id,
                'is_active'  => (bool) $row->is_active,
            ];
        })->values()->all();
    }

    /**
     * Assert user may login via mobile API.
     *
     * @throws ValidationException-compatible array via InvalidArgumentException message
     */
    public function assertMobileLoginAllowed(User $user): void
    {
        if ($user->registration_status === 'pending') {
            throw new InvalidArgumentException('Akun masih menunggu persetujuan admin.');
        }

        if ($user->registration_status === 'rejected') {
            throw new InvalidArgumentException('Registrasi akun ditolak. Hubungi administrator.');
        }

        if (!in_array((int) $user->current_role_id, self::PESERTA_ROLE_IDS, true)) {
            return;
        }

        if (!$this->resolvePeserta($user)) {
            throw new InvalidArgumentException('Profil peserta belum terhubung. Hubungi administrator.');
        }
    }

    private function clearPesertaLinkFromUser(User $user, int $newPesertaId, string $newPesertaType): void
    {
        if ($user->peserta_id
            && ((int) $user->peserta_id !== $newPesertaId || $user->peserta_type !== $newPesertaType)) {
            $oldModelClass = $this->modelClassForType($user->peserta_type ?? '');
            if ($oldModelClass) {
                $oldModelClass::where('id', $user->peserta_id)
                    ->where('users_id', $user->id)
                    ->update(['users_id' => null]);
            }
        }

        foreach (self::PESERTA_TYPE_TO_MODEL as $type => $modelClass) {
            if ($type === $newPesertaType) {
                continue;
            }
            $modelClass::where('users_id', $user->id)->update(['users_id' => null]);
        }
    }
}
