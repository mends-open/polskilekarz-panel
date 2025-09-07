<?php

namespace App\Jobs;

use App\Jobs\ImportEmaMedications;
use App\Jobs\UpsertEmaMedicationData;
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
use PhpOffice\PhpSpreadsheet\IOFactory;

class QueueEmaMedicationsImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public ?string $endpoint = null)
    {
    }

    public function handle(): void
    {
        $endpoint = $this->endpoint ?? 'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx';

        $localPath = Storage::path('ema-medications.xlsx');

        $response = Http::sink($localPath)->get($endpoint);
        $response->throw();

        Log::info('Downloaded EMA spreadsheet', [
            'endpoint' => $endpoint,
            'local_path' => $localPath,
        ]);

        $chunks = $this->convertToCsvChunks($localPath);

        foreach ($chunks as $chunk) {
            Bus::chain([
                new UpsertEmaMedicationData($chunk),
                new ImportEmaMedications($chunk),
            ])->dispatch();
        }

        Log::info('Dispatched EMA medication import jobs', ['chunks' => count($chunks)]);
    }

    /**
     * Convert the EMA spreadsheet into chunked CSV files containing only relevant columns.
     *
     * @return array<int, string> Absolute paths to the generated chunk files
     */
    private function convertToCsvChunks(string $xlsxPath, int $chunkSize = 1000): array
    {
        $sheet = IOFactory::load($xlsxPath)->getActiveSheet();
        $headerRow = 20;
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();

        $headers = [];
        for ($col = 1; $col <= $highestColumn; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $value = (string) $sheet->getCell($column.$headerRow)->getValue();
            $headers[$col] = trim(strtok($value, "\n"));
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

        $paths = [];
        $chunkIndex = 0;
        $rowCount = 0;
        $handle = null;

        $open = function () use (&$chunkIndex, &$paths, &$handle, &$rowCount) {
            $path = Storage::path('ema-medications-chunk-' . $chunkIndex . '.csv');
            $handle = fopen($path, 'w');
            $paths[] = $path;
            $rowCount = 0;
            $chunkIndex++;
        };

        $open();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $productCol = Coordinate::stringFromColumnIndex($map['product_name']);
            $product = trim((string) $sheet->getCell($productCol . $row)->getValue());
            if ($product === '') {
                continue;
            }

            $countryCol = Coordinate::stringFromColumnIndex($map['product_authorisation_country']);
            $routeCol = Coordinate::stringFromColumnIndex($map['route_of_administration']);
            $substanceCol = Coordinate::stringFromColumnIndex($map['active_substance']);

            $country = trim((string) $sheet->getCell($countryCol . $row)->getValue());
            $routes = trim((string) $sheet->getCell($routeCol . $row)->getValue());
            $substances = trim((string) $sheet->getCell($substanceCol . $row)->getValue());

            fputcsv($handle, [$product, $country, $routes, $substances]);
            $rowCount++;

            if ($rowCount >= $chunkSize) {
                fclose($handle);
                $open();
            }
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        Log::info('Converted EMA spreadsheet to CSV chunks', [
            'source' => $xlsxPath,
            'chunks' => count($paths),
        ]);

        return $paths;
    }
}

