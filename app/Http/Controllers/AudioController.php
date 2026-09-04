<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\AudioConfigurationService;
use App\Services\ExternalAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioController extends Controller
{
    /**
     * Get audio URL for announcement
     */
    public function getAnnouncementAudio(Request $request, ExternalAudioService $audioService): JsonResponse
    {
        $validated = $request->validate([
            'queue_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $savedAudioUrl = app(AudioConfigurationService::class)->get()['url'] ?? null;
        $defaultAudioUrl = $savedAudioUrl ?: asset(config('audio.fallback.url', 'sounds/opening.mp3'));

        if (empty($validated['queue_id'])) {
            return response()->json([
                'success' => true,
                'audioUrl' => $defaultAudioUrl,
            ]);
        }

        $query = Queue::query()
            ->with('service.instansi')
            ->whereIn('status', Queue::ACTIVE_STATUSES)
            ->whereDate('created_at', now()->toDateString());

        if ($request->user()->isOperator()) {
            $query->where('counter_id', $request->user()->counter_id);
        }

        $queue = $query->findOrFail($validated['queue_id']);
        $service = $queue->service;
        abort_unless($service, 404, 'Layanan tidak ditemukan.');

        $counterName = $queue->counter?->display_name ?? 'Loket';
        $zona = $service->instansi?->zone ?? 'Zona';
        $text = "Nomor antrian {$queue->number}, layanan {$service->name}, menuju ke loket {$counterName}, {$zona}. Terima kasih.";
        $audioUrl = $audioService->generateAudioUrl($text, config('audio.default_service', 'default'));

        return response()->json([
            'success' => true,
            'audioUrl' => $audioUrl,
            'queueNumber' => $queue->number,
            'serviceName' => $service->name,
            'counterName' => $counterName,
            'zona' => $zona,
        ]);
    }

    /**
     * Upload custom audio file
     */
    public function uploadAudio(Request $request): JsonResponse
    {
        $allowedFormats = implode(',', config('audio.file_management.allowed_formats', ['mp3', 'wav', 'ogg']));
        $maxFileSize = (int) config('audio.file_management.max_file_size', 10240);
        $disk = config('audio.file_management.storage_disk', 'public');
        $storagePath = trim(config('audio.file_management.storage_path', 'audio'), '/');

        $request->validate([
            'audio' => "required|file|mimes:{$allowedFormats}|max:{$maxFileSize}",
            'name' => 'required|string|max:255',
        ]);

        $file = $request->file('audio');
        $baseName = Str::slug(pathinfo($request->string('name')->toString(), PATHINFO_FILENAME)) ?: 'audio';
        $filename = now()->format('YmdHis').'_'.$baseName.'.'.$file->getClientOriginalExtension();
        $filepath = $file->storeAs($storagePath, $filename, $disk);

        return response()->json([
            'success' => true,
            'message' => 'Audio berhasil diupload',
            'audioUrl' => Storage::disk($disk)->url($filepath),
            'filename' => $filename,
        ]);
    }

    /**
     * Get list of available audio files
     */
    public function getAudioList(): JsonResponse
    {
        $disk = config('audio.file_management.storage_disk', 'public');
        $storagePath = trim(config('audio.file_management.storage_path', 'audio'), '/');
        $storage = Storage::disk($disk);
        $audioFiles = $storage->files($storagePath);
        $audioList = [];

        foreach ($audioFiles as $file) {
            $audioList[] = [
                'filename' => basename($file),
                'url' => $storage->url($file),
                'size' => $storage->size($file),
                'lastModified' => $storage->lastModified($file),
            ];
        }

        return response()->json([
            'success' => true,
            'audioList' => $audioList,
        ]);
    }

    /**
     * Delete audio file
     */
    public function deleteAudio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/'],
        ]);
        $disk = config('audio.file_management.storage_disk', 'public');
        $storagePath = trim(config('audio.file_management.storage_path', 'audio'), '/');
        $storage = Storage::disk($disk);
        $filepath = $storagePath.'/'.$validated['filename'];

        if ($storage->exists($filepath)) {
            $storage->delete($filepath);

            return response()->json([
                'success' => true,
                'message' => 'Audio berhasil dihapus',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File tidak ditemukan',
        ], 404);
    }
}
