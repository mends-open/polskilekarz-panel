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

    public int $timeout = 600;

    public function __construct(public string $path, public int $chunkSize = 500)
    {
    }

    public function handle(): void
    {
        $csvPath = Storage::path($this->path);
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            Log::warning('Unable to open EMA CSV for chunking', ['path' => $csvPath]);
            return;
        }

        Storage::makeDirectory('ema/chunks');

        $chunkIndex = 0;
        $rowCount = 0;
        $chunkHandle = fopen(Storage::path('ema/chunks/chunk-' . $chunkIndex . '.csv'), 'w');

        while (($row = fgetcsv($handle)) !== false) {
            fputcsv($chunkHandle, $row);
            $rowCount++;
            if ($rowCount % $this->chunkSize === 0) {
                fclose($chunkHandle);
                $chunkIndex++;
                $chunkHandle = fopen(Storage::path('ema/chunks/chunk-' . $chunkIndex . '.csv'), 'w');
            }
        }

        fclose($chunkHandle);
        fclose($handle);

        Storage::delete($this->path);

        Log::info('Chunked EMA CSV', ['chunks' => $chunkIndex + 1]);

        ProcessEmaCsvChunks::dispatch();
    }
}
