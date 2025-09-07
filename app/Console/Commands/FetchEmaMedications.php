<?php

namespace App\Console\Commands;

use App\Jobs\DownloadEmaMedications;
use Illuminate\Console\Command;

class FetchEmaMedications extends Command
{
    protected $signature = 'medications:fetch {--endpoint=}';

    protected $description = 'Queue EMA medication import';

    /**
     * Execute the console command.
     *
     * Dispatches the EMA medication import job to run in the background.
     */
    public function handle(): int
    {
        DownloadEmaMedications::dispatch($this->option('endpoint'));

        $this->info('EMA medication import queued.');

        return Command::SUCCESS;
    }
}
