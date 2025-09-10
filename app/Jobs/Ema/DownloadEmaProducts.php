<?php

namespace App\Jobs\Ema;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($xlsxPath);
        $worksheetInfo = $info[0];
        $highestRow = (int) $worksheetInfo['totalRows'];
        $highestColumn = (int) $worksheetInfo['lastColumnIndex'];

        $sheet = $reader->load($xlsxPath)->getActiveSheet();

        $headerRow = null;
        $headers = [];
        for ($row = 1; $row <= 50; $row++) {
            $rowHeaders = [];
            for ($col = 1; $col <= $highestColumn; $col++) {
                $coordinate = Coordinate::stringFromColumnIndex($col) . $row;
                $value = (string) $sheet->getCell($coordinate)->getValue();
                $rowHeaders[$col] = trim(strtok($value, "\n"));
            }
            if (in_array('Product name', $rowHeaders, true)) {
                $headerRow = $row;
                $headers = $rowHeaders;
                break;
            }
        }

        if ($headerRow === null) {
            Log::warning('EMA header row not found', ['file' => $xlsxPath]);
            return;
        }

        $map = [
            'product_name' => null,
            'product_authorisation_country' => null,
            'route_of_administration' => null,
            'active_substance' => null,
        ];

        foreach ($headers as $index => $name) {
            $key = Str::snake($name);
            if (array_key_exists($key, $map)) {
                $map[$key] = $index;
            }
        }

        if (in_array(null, $map, true)) {
            Log::warning('Required EMA columns missing', ['map' => $map]);
            return;
        }

        $chunkSize = (int) config('services.european_medicines_agency.chunk_size', 500);
        $jobs = [];
        for ($start = $headerRow + 1; $start <= $highestRow; $start += $chunkSize) {
            $end = min($start + $chunkSize - 1, $highestRow);
            $jobs[] = new ImportEmaProducts($xlsxRelative, $start, $end, $map);
        }

        Bus::batch($jobs)->dispatch();
    }
}
