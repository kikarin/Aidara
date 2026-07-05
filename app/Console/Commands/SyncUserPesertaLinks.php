<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserPesertaLinkService;
use Illuminate\Console\Command;

class SyncUserPesertaLinks extends Command
{
    protected $signature = 'aidara:sync-user-peserta-links {--dry-run : Preview changes without writing}';

    protected $description = 'Repair user ↔ peserta links for role Atlet/Pelatih/Tenaga Pendukung';

    public function handle(): int
    {
        $dryRun      = (bool) $this->option('dry-run');
        $linkService = app(UserPesertaLinkService::class);
        $fixed       = 0;
        $orphans     = 0;

        $this->info($dryRun ? 'DRY RUN — no changes will be saved' : 'Syncing user-peserta links...');
        $this->newLine();

        User::query()
            ->whereIn('current_role_id', UserPesertaLinkService::PESERTA_ROLE_IDS)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($linkService, $dryRun, &$fixed, &$orphans) {
                foreach ($users as $user) {
                    $peserta = $linkService->resolvePeserta($user, autoSync: false);

                    if ($peserta) {
                        $pesertaType = $user->peserta_type
                            ?: $linkService->pesertaTypeFromRole((int) $user->current_role_id);

                        $needsSync = (int) $user->peserta_id !== (int) $peserta->id
                            || $user->peserta_type !== $pesertaType
                            || (int) $peserta->users_id !== (int) $user->id;

                        if ($needsSync) {
                            $this->line("FIX user #{$user->id} ({$user->email}) → {$pesertaType} #{$peserta->id}");
                            if (!$dryRun) {
                                $linkService->link($user->fresh(), $pesertaType, $peserta->id);
                            }
                            $fixed++;
                        }

                        continue;
                    }

                    $this->warn("ORPHAN user #{$user->id} ({$user->email}) role {$user->current_role_id} — no peserta found");
                    $orphans++;
                }
            });

        $this->newLine();
        $this->info("Done. Fixed: {$fixed}, Orphans: {$orphans}");

        return self::SUCCESS;
    }
}
