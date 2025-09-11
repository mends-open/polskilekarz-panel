<?php

namespace App\Jobs\Ema;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\Ema\ConvertEmaProductsToCsv;

class DownloadEmaProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $endpoint = null)
    {
    }

    public function handle(): void
    {
        $storage = config('services.european_medicines_agency.storage_dir', 'ema');
        $disk = config('services.european_medicines_agency.storage_disk');
        $endpoint = $this->endpoint ?? config('services.european_medicines_agency.endpoint');

        $store = Storage::disk($disk);
        $store->deleteDirectory($storage);
        $store->makeDirectory($storage);

        $xlsxRelative = "{$storage}/products.xlsx";
        $xlsxPath = $store->path($xlsxRelative);

        Http::sink($xlsxPath)->get($endpoint)->throw();

        Log::info('Downloaded EMA spreadsheet', [
            'endpoint' => $endpoint,
            'path' => $xlsxPath,
        ]);

        $csvRelative = "{$storage}/products.csv";

        ConvertEmaProductsToCsv::dispatch($xlsxRelative, $csvRelative);
    }
}
