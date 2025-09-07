<?php

namespace App\Console\Commands;

use App\Jobs\QueueEmaMedicationsImport;
use Illuminate\Console\Command;

class FetchEmaMedications extends Command
{
    protected $signature = 'medications:fetch {--endpoint=}';

    protected $description = 'Queue EMA medication import';

    /**
     * Queue the EMA medication import job.
     */
    public function handle(): int
    {
        QueueEmaMedicationsImport::dispatch($this->option('endpoint'));

        $this->info('EMA medication import queued.');

        return Command::SUCCESS;
    }
}
