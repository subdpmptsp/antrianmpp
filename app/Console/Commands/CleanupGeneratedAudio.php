<?php

namespace App\Console\Commands;

use App\Services\ExternalAudioService;
use Illuminate\Console\Command;

class CleanupGeneratedAudio extends Command
{
    protected $signature = 'audio:cleanup-generated {--days=7 : Umur minimum file dalam hari}';

    protected $description = 'Hapus cache audio TTS hasil generator yang sudah kedaluwarsa';

    public function handle(ExternalAudioService $audio): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($days === false) {
            $this->error('Opsi --days harus berupa angka minimal 1.');

            return self::INVALID;
        }

        $deleted = $audio->cleanupOldAudioFiles($days);
        $this->info("{$deleted} file audio TTS lama berhasil dibersihkan.");

        return self::SUCCESS;
    }
}
