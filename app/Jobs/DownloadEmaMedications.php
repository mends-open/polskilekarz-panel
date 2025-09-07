<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class DownloadEmaMedications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public ?string $endpoint = null)
    {
    }

    public function handle(): void
    {
        $endpoint = $this->endpoint ?? 'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx';

        Storage::makeDirectory('ema');
        $xlsxPath = Storage::path('ema/medications.xlsx');

        Http::sink($xlsxPath)->get($endpoint)->throw();

        Log::info('Downloaded EMA spreadsheet', [
            'endpoint' => $endpoint,
            'path' => $xlsxPath,
        ]);

        $csvRelative = 'ema/medications.csv';
        $csvPath = Storage::path($csvRelative);

        $this->convertToCsv($xlsxPath, $csvPath);

        Log::info('Converted EMA spreadsheet to CSV', ['path' => $csvPath]);

        ChunkEmaMedicationsCsv::dispatch($csvRelative);
    }

    private function convertToCsv(string $xlsxPath, string $csvPath): void
    {
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

        $handle = fopen($csvPath, 'w');
        if (!$handle) {
            Log::warning('Unable to open CSV for writing', ['path' => $csvPath]);
            return;
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $productCoordinate = Coordinate::stringFromColumnIndex($map['product_name']) . $row;
            $product = trim((string) $sheet->getCell($productCoordinate)->getValue());
            if ($product === '') {
                continue;
            }

            $countryCoordinate = Coordinate::stringFromColumnIndex($map['product_authorisation_country']) . $row;
            $routesCoordinate = Coordinate::stringFromColumnIndex($map['route_of_administration']) . $row;
            $substancesCoordinate = Coordinate::stringFromColumnIndex($map['active_substance']) . $row;

            $country = trim((string) $sheet->getCell($countryCoordinate)->getValue());
            $routes = trim((string) $sheet->getCell($routesCoordinate)->getValue());
            $substances = trim((string) $sheet->getCell($substancesCoordinate)->getValue());

            fputcsv($handle, [$product, $country, $routes, $substances]);
        }

        fclose($handle);

        unset($sheet);
        gc_collect_cycles();
    }
}
