<?php

namespace App\Console\Commands;

use App\Jobs\DownloadEmaMedications;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchEmaMedications extends Command
{
    protected $signature = 'medications:fetch {--endpoint=}';

    protected $description = 'Queue EMA medication import';

    /**
     * Dispatches the EMA medication import job and exits immediately.
     */
    public function handle(): int
    {
        $storage = config('ema.storage_dir', 'ema');
        $endpoint = $this->option('endpoint') ?? config('ema.endpoint');

        Storage::deleteDirectory($storage);
        DownloadEmaMedications::dispatch($endpoint);

        $this->info('EMA medication import queued.');
        Log::info('EMA medication import queued', [
            'endpoint' => $endpoint,
        ]);

        return Command::SUCCESS;
    }
}
