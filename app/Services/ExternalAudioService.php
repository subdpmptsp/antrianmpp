<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExternalAudioService
{
    /**
     * Generate audio URL from external service
     */
    public function generateAudioUrl(string $text, string $service = 'default'): string
    {
        switch ($service) {
            case 'google':
                return $this->generateGoogleTTS($text);
            case 'elevenlabs':
                return $this->generateElevenLabsTTS($text);
            case 'azure':
                return $this->generateAzureTTS($text);
            case 'responsivevoice':
                return $this->generateResponsiveVoiceTTS($text);
            case 'custom':
                return $this->generateCustomAudio($text);
            default:
                return $this->generateDefaultAudio($text);
        }
    }

    /**
     * Generate audio using Google Text-to-Speech
     */
    private function generateGoogleTTS(string $text): string
    {
        $apiKey = config('audio.google.api_key');

        if (! $apiKey) {
            Log::warning('Google TTS API key not configured');

            return $this->getDefaultAudioUrl();
        }

        try {
            $response = Http::post("https://texttospeech.googleapis.com/v1/text:synthesize?key={$apiKey}", [
                'input' => ['text' => $text],
                'voice' => [
                    'languageCode' => 'id-ID',
                    'name' => 'id-ID-Wavenet-A',
                    'ssmlGender' => 'FEMALE',
                ],
                'audioConfig' => [
                    'audioEncoding' => 'MP3',
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $audioContent = base64_decode($data['audioContent']);

                $filename = 'google_tts_'.time().'.mp3';
                $filepath = 'audio/'.$filename;

                Storage::put($filepath, $audioContent);

                return Storage::url($filepath);
            }
        } catch (\Exception $e) {
            Log::error('Google TTS error: '.$e->getMessage());
        }

        return $this->getDefaultAudioUrl();
    }

    /**
     * Generate audio using ElevenLabs
     */
    private function generateElevenLabsTTS(string $text): string
    {
        $apiKey = config('audio.elevenlabs.api_key');
        $voiceId = config('audio.elevenlabs.voice_id', 'pNInz6obpgDQGcFmaJgB');

        if (! $apiKey) {
            Log::warning('ElevenLabs API key not configured');

            return $this->getDefaultAudioUrl();
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'audio/mpeg',
                'Content-Type' => 'application/json',
                'xi-api-key' => $apiKey,
            ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.5,
                ],
            ]);

            if ($response->successful()) {
                $filename = 'elevenlabs_tts_'.time().'.mp3';
                $filepath = 'audio/'.$filename;

                Storage::put($filepath, $response->body());

                return Storage::url($filepath);
            }
        } catch (\Exception $e) {
            Log::error('ElevenLabs TTS error: '.$e->getMessage());
        }

        return $this->getDefaultAudioUrl();
    }

    /**
     * Generate audio using Azure Cognitive Services
     */
    private function generateAzureTTS(string $text): string
    {
        $apiKey = config('audio.azure.api_key');
        $region = config('audio.azure.region', 'eastus');

        if (! $apiKey) {
            Log::warning('Azure TTS API key not configured');

            return $this->getDefaultAudioUrl();
        }

        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $apiKey,
                'Content-Type' => 'application/ssml+xml',
                'X-Microsoft-OutputFormat' => 'audio-16khz-128kbitrate-mono-mp3',
            ])->post("https://{$region}.tts.speech.microsoft.com/cognitiveservices/v1", [
                'body' => $this->generateSSML($text),
            ]);

            if ($response->successful()) {
                $filename = 'azure_tts_'.time().'.mp3';
                $filepath = 'audio/'.$filename;

                Storage::put($filepath, $response->body());

                return Storage::url($filepath);
            }
        } catch (\Exception $e) {
            Log::error('Azure TTS error: '.$e->getMessage());
        }

        return $this->getDefaultAudioUrl();
    }

    /**
     * Generate audio using ResponsiveVoice
     */
    private function generateResponsiveVoiceTTS(string $text): string
    {
        // ResponsiveVoice tidak menghasilkan URL audio file
        // Melainkan langsung memutar audio di browser
        // Kita return special indicator untuk frontend
        return 'responsivevoice://'.base64_encode($text);
    }

    /**
     * Generate custom audio from external URL
     */
    private function generateCustomAudio(string $text): string
    {
        $customUrl = config('audio.custom.url');

        if (! $customUrl) {
            return $this->getDefaultAudioUrl();
        }

        try {
            // Replace placeholders in URL with actual values
            $url = str_replace([
                '{text}',
                '{queueNumber}',
                '{serviceName}',
                '{counterName}',
                '{zona}',
            ], [
                urlencode($text),
                urlencode($this->extractQueueNumber($text)),
                urlencode($this->extractServiceName($text)),
                urlencode($this->extractCounterName($text)),
                urlencode($this->extractZona($text)),
            ], $customUrl);

            // Test if URL is accessible
            $response = Http::head($url);

            if ($response->successful()) {
                return $url;
            }
        } catch (\Exception $e) {
            Log::error('Custom audio URL error: '.$e->getMessage());
        }

        return $this->getDefaultAudioUrl();
    }

    /**
     * Generate default audio (fallback)
     */
    private function generateDefaultAudio(string $text): string
    {
        return $this->getDefaultAudioUrl();
    }

    /**
     * Get default audio URL
     */
    private function getDefaultAudioUrl(): string
    {
        return asset(config('audio.fallback.url', 'sounds/opening.mp3'));
    }

    /**
     * Generate SSML for Azure TTS
     */
    private function generateSSML(string $text): string
    {
        return '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="id-ID">
            <voice name="id-ID-GadisNeural">
                '.htmlspecialchars($text).'
            </voice>
        </speak>';
    }

    /**
     * Extract queue number from text
     */
    private function extractQueueNumber(string $text): string
    {
        if (preg_match('/Nomor antrian ([^,]+)/', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract service name from text
     */
    private function extractServiceName(string $text): string
    {
        if (preg_match('/layanan ([^,]+)/', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract counter name from text
     */
    private function extractCounterName(string $text): string
    {
        if (preg_match('/menuju ke ([^,]+)/', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract zona from text
     */
    private function extractZona(string $text): string
    {
        if (preg_match('/ZONA ([^.]*)/', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Clean up old audio files
     */
    public function cleanupOldAudioFiles(int $daysOld = 7): int
    {
        $deletedCount = 0;
        $cutoffTime = now()->subDays($daysOld)->timestamp;
        $disk = Storage::disk('local');
        $audioFiles = $disk->files('audio');

        foreach ($audioFiles as $file) {
            $filename = basename($file);
            $isGeneratedTts = preg_match('/^(google|elevenlabs|azure)_tts_.*\.mp3$/i', $filename) === 1;

            if ($isGeneratedTts && $disk->lastModified($file) < $cutoffTime) {
                $disk->delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
