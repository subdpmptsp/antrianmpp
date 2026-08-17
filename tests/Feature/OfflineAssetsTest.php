<?php

namespace Tests\Feature;

use Tests\TestCase;

class OfflineAssetsTest extends TestCase
{
    public function test_views_do_not_load_remote_fonts_or_tts_scripts(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $this->assertStringNotContainsString('fonts.googleapis.com', $contents, $file->getPathname());
            $this->assertStringNotContainsString(
                '<script src="https://code.responsivevoice.org',
                $contents,
                $file->getPathname(),
            );
        }

        $this->assertFileExists(public_path('sounds/opening.mp3'));
    }
}
