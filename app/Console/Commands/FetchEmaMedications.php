<?php

namespace App\Console\Commands;

use App\Jobs\ImportEmaMedications;
use App\Jobs\UpsertEmaMedicationData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchEmaMedications extends Command
{
    protected $signature = 'medications:fetch {--endpoint=}';

    protected $description = 'Fetch EMA product data and dispatch import jobs';

    public function handle(): int
    {
        $endpoint = $this->option('endpoint') ?? 'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx';

        $path = Storage::path('ema-medications.xlsx');

        Http::sink($path)->get($endpoint);

        Log::info('Downloaded EMA spreadsheet', [
            'endpoint' => $endpoint,
            'path' => $path,
        ]);

        Bus::batch([
            new UpsertEmaMedicationData($path),
        ])->name('ema-medications-import')
            ->then(function () use ($path) {
                Log::info('Upsert batch finished; dispatching import job', ['path' => $path]);
                ImportEmaMedications::dispatch($path);
            })
            ->dispatch();

        $this->info('Import batch dispatched.');

        return self::SUCCESS;
    }
}
