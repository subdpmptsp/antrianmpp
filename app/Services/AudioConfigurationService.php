<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioConfigurationService
{
    private const CONFIG_PATH = 'audio/config.json';

    public function get(): array
    {
        $disk = Storage::disk('local');

        if (!$disk->exists(self::CONFIG_PATH)) {
            return $this->defaults();
        }

        $config = json_decode($disk->get(self::CONFIG_PATH), true);

        return is_array($config)
            ? array_merge($this->defaults(), $config)
            : $this->defaults();
    }

    public function save(array $config): array
    {
        $stored = array_merge($this->defaults(), [
            'url' => $config['url'] ?? null,
            'name' => $config['name'] ?? 'Audio Pemanggilan',
            'description' => $config['description'] ?? null,
            'type' => $config['type'] ?? 'announcement',
            'tts' => array_merge($this->defaults()['tts'], $config['tts'] ?? []),
            'updated_at' => now()->toIso8601String(),
        ]);

        $written = Storage::disk('local')->put(
            self::CONFIG_PATH,
            json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        if (!$written) {
            throw new RuntimeException('Konfigurasi audio gagal disimpan.');
        }

        return $stored;
    }

    private function defaults(): array
    {
        return [
            'url' => null,
            'name' => 'Audio Lokal Bawaan',
            'description' => null,
            'type' => 'announcement',
            'tts' => [
                'voice' => 'auto',
                'rate' => 0.9,
                'pitch' => 1.0,
                'volume' => 1.0,
            ],
            'updated_at' => null,
        ];
    }
}
