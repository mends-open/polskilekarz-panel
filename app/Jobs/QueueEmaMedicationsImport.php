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
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

        $upsertJobs = [];
        $importJobs = [];
        foreach ($chunks as $chunk) {
            $upsertJobs[] = new UpsertEmaMedicationData($chunk);
            $importJobs[] = new ImportEmaMedications($chunk);
        }

        Bus::batch($upsertJobs)
            ->name('ema-medications-upsert')
            ->then(fn () => Bus::batch($importJobs)->name('ema-medications-import')->dispatch())
            ->dispatch();

        Log::info('Dispatched EMA medication import jobs', ['chunks' => count($chunks)]);
    }

    /**
     * Convert the EMA spreadsheet into chunked CSV files containing only relevant columns.
     *
     * @return array<int, string> Absolute paths to the generated chunk files
     */
    private function convertToCsvChunks(string $xlsxPath, int $chunkSize = 500): array
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($xlsxPath);
        $worksheetInfo = $info[0];
        $highestRow = (int) $worksheetInfo['totalRows'];
        $highestColumn = (int) $worksheetInfo['lastColumnIndex'];

        $filter = new class implements IReadFilter {
            private int $startRow = 0;
            private int $endRow = 0;

            public function setRows(int $startRow, int $chunkSize): void
            {
                $this->startRow = $startRow;
                $this->endRow = $startRow + $chunkSize - 1;
            }

            public function readCell($column, $row, $worksheetName = ''): bool
            {
                return $row >= $this->startRow && $row <= $this->endRow;
            }
        };

        $reader->setReadFilter($filter);

        // Locate header row within the first 50 lines
        $filter->setRows(1, 50);
        $sheet = $reader->load($xlsxPath)->getActiveSheet();

        $headerRow = null;
        $headers = [];
        for ($row = 1; $row <= 50; $row++) {
            $rowHeaders = [];
            for ($col = 1; $col <= $highestColumn; $col++) {
                $value = (string) $sheet->getCellByColumnAndRow($col, $row)->getValue();
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
            return [];
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
            return [];
        }

        $paths = [];
        $chunkIndex = 0;

        for ($start = $headerRow + 1; $start <= $highestRow; $start += $chunkSize) {
            $filter->setRows($start, $chunkSize);
            $sheet = $reader->load($xlsxPath)->getActiveSheet();

            $path = Storage::path('ema-medications-chunk-' . $chunkIndex . '.csv');
            $handle = fopen($path, 'w');

            $end = min($start + $chunkSize - 1, $highestRow);
            for ($row = $start; $row <= $end; $row++) {
                $product = trim((string) $sheet->getCellByColumnAndRow($map['product_name'], $row)->getValue());
                if ($product === '') {
                    continue;
                }

                $country = trim((string) $sheet->getCellByColumnAndRow($map['product_authorisation_country'], $row)->getValue());
                $routes = trim((string) $sheet->getCellByColumnAndRow($map['route_of_administration'], $row)->getValue());
                $substances = trim((string) $sheet->getCellByColumnAndRow($map['active_substance'], $row)->getValue());

                fputcsv($handle, [$product, $country, $routes, $substances]);
            }

            fclose($handle);
            $paths[] = $path;
            $chunkIndex++;

            unset($sheet);
            gc_collect_cycles();
        }

        Log::info('Converted EMA spreadsheet to CSV chunks', [
            'source' => $xlsxPath,
            'chunks' => count($paths),
        ]);

        return $paths;
    }
}

