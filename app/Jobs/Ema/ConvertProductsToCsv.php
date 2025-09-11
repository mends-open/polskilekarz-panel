<?php

namespace App\Jobs\Ema;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ConvertProductsToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public string $source, public string $target)
    {
    }

    public function handle(): void
    {
        $disk = config('services.european_medicines_agency.storage_disk');
        $store = Storage::disk($disk);
        $sourcePath = $store->path($this->source);
        $targetPath = $store->path($this->target);

        if ($this->convert($sourcePath, $targetPath)) {
            Log::info('Converted EMA spreadsheet to CSV', ['path' => $targetPath]);

            ChunkProductsCsv::dispatch($this->target);
        }
    }

    private function convert(string $xlsxPath, string $csvPath): bool
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($xlsxPath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

            $headerRow = null;
            $headers = [];
            for ($row = 1; $row <= 50 && $row <= $highestRow; $row++) {
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
                return false;
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
                return false;
            }

            $handle = fopen($csvPath, 'w');
            if (!$handle) {
                Log::warning('Unable to open CSV for writing', ['path' => $csvPath]);
                return false;
            }

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $product = trim((string) $sheet->getCellByColumnAndRow($map['product_name'], $row)->getValue());
                if ($product === '') {
                    continue;
                }

                $country = trim((string) $sheet->getCellByColumnAndRow($map['product_authorisation_country'], $row)->getValue());
                $routesRaw = (string) $sheet->getCellByColumnAndRow($map['route_of_administration'], $row)->getValue();
                $substances = trim((string) $sheet->getCellByColumnAndRow($map['active_substance'], $row)->getValue());

                $routes = Str::of($routesRaw)
                    ->replace(["\r\n", "\r", "\n", "\t", ',', ';', '/'], '|')
                    ->explode('|')
                    ->map(fn ($r) => trim($r))
                    ->filter()
                    ->implode('|');

                fputcsv($handle, [$product, $country, $routes, $substances]);
            }

            fclose($handle);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return true;
        } catch (\Throwable $e) {
            Log::warning('EMA spreadsheet conversion failed', [
                'file' => $xlsxPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

