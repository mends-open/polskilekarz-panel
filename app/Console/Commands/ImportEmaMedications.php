<?php

namespace App\Console\Commands;

use App\Jobs\ImportEmaMedicationsJob;
use Illuminate\Console\Command;

class ImportEmaMedications extends Command
{
    protected $signature = 'medications:update';

    protected $description = 'Download and import medications from EMA data';

    public function handle(): void
    {
        $url = $this->ask(
            'EMA XLSX url',
            'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx'
        );

        ImportEmaMedicationsJob::dispatch($url);

        $this->info('Import dispatched to queue.');
    }
}
