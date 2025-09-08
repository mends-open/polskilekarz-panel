<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChunkEmaMedicationsCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path, public int $chunkSize = 0)
    {
        $this->chunkSize = $chunkSize ?: (int) config('ema.chunk_size', 500);
    }

    public function handle(): void
    {
        $disk = config('ema.storage_disk');
        $store = Storage::disk($disk);
        $csvPath = $store->path($this->path);
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            Log::warning('Unable to open EMA CSV for chunking', ['path' => $csvPath]);
            return;
        }

        $storage = config('ema.storage_dir', 'ema');
        $store->makeDirectory("{$storage}/chunks");

        $chunkIndex = 1;
        $rowCount = 0;
        $chunkHandle = fopen($store->path(sprintf('%s/chunks/chunk-%04d.csv', $storage, $chunkIndex)), 'w');

        while (($row = fgetcsv($handle)) !== false) {
            if ($rowCount > 0 && $rowCount % $this->chunkSize === 0) {
                fclose($chunkHandle);
                $chunkIndex++;
                $chunkHandle = fopen($store->path(sprintf('%s/chunks/chunk-%04d.csv', $storage, $chunkIndex)), 'w');
            }

            fputcsv($chunkHandle, $row);
            $rowCount++;
        }

        fclose($chunkHandle);
        fclose($handle);

        $store->delete($this->path);

        Log::info('Chunked EMA CSV', [
            'chunks' => $chunkIndex,
            'rows' => $rowCount,
        ]);

        ProcessEmaCsvChunks::dispatch();
    }
}
