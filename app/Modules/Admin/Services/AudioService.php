<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AudioService
{
    /**
     * Stores an uploaded audio file for a sentence. The first file of a
     * sentence always becomes its primary file. The file name is generated
     * here; the client's name is only kept for display.
     */
    public function store(
        Sentence $sentence,
        UploadedFile $file,
        ?User $uploader,
        bool $makePrimary = false,
        ?int $durationMs = null,
        ?string $voiceLabel = null,
    ): SentenceAudio {
        $disk = (string) config('learning.audio.disk');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, config('learning.audio.extensions'), true)) {
            throw new RuntimeException('Unsupported audio extension.');
        }

        $path = $file->storeAs("audio/{$sentence->id}", Str::ulid().'.'.$extension, ['disk' => $disk]);

        if ($path === false) {
            throw new RuntimeException('The audio file could not be stored.');
        }

        try {
            return DB::transaction(function () use ($sentence, $file, $uploader, $makePrimary, $durationMs, $voiceLabel, $disk, $path): SentenceAudio {
                $isPrimary = $makePrimary || ! $sentence->audio()->where('is_primary', true)->exists();

                if ($isPrimary) {
                    $sentence->audio()->where('is_primary', true)->update(['is_primary' => false]);
                }

                return SentenceAudio::create([
                    'sentence_id' => $sentence->id,
                    'uploaded_by' => $uploader?->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'duration_ms' => $durationMs,
                    'voice_label' => $voiceLabel,
                    'is_primary' => $isPrimary,
                ]);
            });
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }
    }

    public function makePrimary(SentenceAudio $audio): void
    {
        DB::transaction(function () use ($audio): void {
            SentenceAudio::where('sentence_id', $audio->sentence_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $audio->update(['is_primary' => true]);
        });
    }

    /**
     * Removes the row and the file. If the primary file is removed, the
     * newest remaining file takes over.
     */
    public function delete(SentenceAudio $audio): void
    {
        DB::transaction(function () use ($audio): void {
            $wasPrimary = $audio->is_primary;
            $sentenceId = $audio->sentence_id;

            $audio->forceDelete();

            if ($wasPrimary) {
                SentenceAudio::where('sentence_id', $sentenceId)
                    ->orderByDesc('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        Storage::disk($audio->disk)->delete($audio->path);
    }
}
