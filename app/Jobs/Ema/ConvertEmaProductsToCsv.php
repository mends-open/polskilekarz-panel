<?php

namespace App\Jobs\Ema;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ConvertEmaProductsToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        $disk = config('services.european_medicines_agency.storage_disk');
        $store = Storage::disk($disk);
        $xlsxPath = $store->path($this->path);
        $csvRelative = Str::replaceLast('.xlsx', '.csv', $this->path);
        $csvPath = $store->path($csvRelative);

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($xlsxPath)->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headerRow = null;
        $headers = [];
        for ($row = 1; $row <= 50; $row++) {
            $rowHeaders = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
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
        $lineCount = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $product = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($map['product_name']) . $row)->getValue();
            $product = trim($product);
            if ($product === '') {
                continue;
            }

            $country = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($map['product_authorisation_country']) . $row)->getValue();
            $routes = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($map['route_of_administration']) . $row)->getValue();
            $substances = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($map['active_substance']) . $row)->getValue();

            fputcsv($handle, [$product, $country, $routes, $substances]);
            $lineCount++;
        }

        fclose($handle);

        Log::info('Converted EMA spreadsheet to CSV', [
            'path' => $csvPath,
            'rows' => $lineCount,
        ]);

        $chunkSize = (int) config('services.european_medicines_agency.chunk_size', 500);
        $jobs = [];
        for ($start = 1; $start <= $lineCount; $start += $chunkSize) {
            $end = min($start + $chunkSize - 1, $lineCount);
            $jobs[] = new ImportEmaProducts($csvRelative, $start, $end);
        }

        Bus::batch($jobs)->dispatch();
    }
}
