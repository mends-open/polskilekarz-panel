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

    public function handle(): void
    {
        $storage = config('medications.ema.storage_dir', 'ema');
        $disk = config('medications.ema.storage_disk');
        $store = Storage::disk($disk);
        $files = collect($store->files("{$storage}/chunks"))->sort()->values();
        if ($files->isEmpty()) {
            $store->deleteDirectory($storage);
            Log::info('EMA CSV chunk processing complete');
            return;
        }

        $file = $files->first();
        $path = $store->path($file);

        Log::info('Dispatching processing jobs for EMA chunk', ['file' => $file]);

        Bus::chain([
            new ImportEmaProducts($path),
            fn () => Storage::disk($disk)->delete($file),
            new self(),
        ])->dispatch();
    }
}
