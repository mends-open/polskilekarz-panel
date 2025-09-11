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

        if ($this->convertToCsv($sourcePath, $targetPath)) {
            Log::info('Converted EMA spreadsheet to CSV', [
                'path' => $targetPath,
            ]);

            ChunkProductsCsv::dispatch($this->target);
        }
    }

    private function convertToCsv(string $xlsxPath, string $csvPath): bool
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($xlsxPath);

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        $headerRow = null;
        foreach ($rows as $index => $row) {
            if (in_array('Product name', $row, true)) {
                $headerRow = $index;
                break;
            }
        }

        if ($headerRow === null) {
            Log::warning('EMA header row not found', ['file' => $xlsxPath]);
            return false;
        }

        $headers = array_map(fn($v) => trim(strtok((string) $v, "\n")), $rows[$headerRow]);
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

        for ($i = $headerRow + 1, $rowCount = count($rows); $i < $rowCount; $i++) {
            $row = $rows[$i];
            $product = trim((string)($row[$map['product_name']] ?? ''));
            if ($product === '') {
                continue;
            }

            $country = trim((string)($row[$map['product_authorisation_country']] ?? ''));
            $routes = Str::of((string)($row[$map['route_of_administration']] ?? ''))
                ->replace(["\r\n", "\r", "\n", "\t", ',', ';', '/'], '|')
                ->explode('|')
                ->map(fn($r) => trim($r))
                ->filter()
                ->implode('|');
            $substances = trim((string)($row[$map['active_substance']] ?? ''));

            fputcsv($handle, [$product, $country, $routes, $substances]);
        }

        fclose($handle);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return true;
    }
}
