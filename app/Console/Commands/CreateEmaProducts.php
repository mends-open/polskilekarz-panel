<?php

namespace App\Console\Commands;

use App\Jobs\DownloadEmaProducts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateEmaProducts extends Command
{
    protected $signature = 'medication:ema-create {--endpoint=}';

    protected $description = 'Initialize EMA products import';

    /**
     * Dispatches the EMA product import job and exits immediately.
     */
    public function handle(): int
    {
        $storage = config('ema.storage_dir', 'ema');
        $disk = config('ema.storage_disk');
        $endpoint = $this->option('endpoint') ?? config('ema.endpoint');

        Storage::disk($disk)->deleteDirectory($storage);
        DownloadEmaProducts::dispatch($endpoint);

        $this->info('EMA product import queued.');
        Log::info('EMA product import queued', [
            'endpoint' => $endpoint,
        ]);

        return Command::SUCCESS;
    }
}
