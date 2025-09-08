<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessEmaCsvChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(): void
    {
        $files = collect(Storage::files('ema/chunks'))->sort()->values();
        if ($files->isEmpty()) {
            Storage::deleteDirectory('ema');
            Log::info('EMA CSV chunk processing complete');
            return;
        }

        $file = $files->first();
        $path = Storage::path($file);

        Log::info('Dispatching processing jobs for EMA chunk', ['file' => $file]);

        Bus::chain([
            new UpsertEmaMedicationData($path),
            new ImportEmaMedications($path),
            fn () => Storage::delete($file),
            new self(),
        ])->dispatch();
    }
}
