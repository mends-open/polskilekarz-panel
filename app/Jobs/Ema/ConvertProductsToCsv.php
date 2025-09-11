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

        $map = [
            'product_name' => null,
            'product_authorisation_country' => null,
            'route_of_administration' => null,
            'active_substance' => null,
        ];

        $headerRow = null;
        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $cell->getValue();
            }

            $headers = array_map(
                fn($v) => Str::snake(trim(strtok((string) $v, "\n"))),
                $values
            );

            $found = array_intersect(array_keys($map), $headers);
            if (count($found) === count($map)) {
                foreach ($headers as $col => $header) {
                    if (array_key_exists($header, $map)) {
                        $map[$header] = $col;
                    }
                }
                $headerRow = $rowIndex;
                break;
            }
        }

        if ($headerRow === null || in_array(null, $map, true)) {
            Log::warning('EMA header row not found', ['file' => $xlsxPath]);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return false;
        }

        $handle = fopen($csvPath, 'w');
        if (!$handle) {
            Log::warning('Unable to open CSV for writing', ['path' => $csvPath]);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return false;
        }

        foreach ($sheet->getRowIterator($headerRow + 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $cell->getValue();
            }

            $product = trim((string)($values[$map['product_name']] ?? ''));
            if ($product === '') {
                continue;
            }

            $country = trim((string)($values[$map['product_authorisation_country']] ?? ''));
            $routes = Str::of((string)($values[$map['route_of_administration']] ?? ''))
                ->replace(["\r\n", "\r", "\n", "\t", ',', ';', '/'], '|')
                ->explode('|')
                ->map(fn($r) => trim($r))
                ->filter()
                ->implode('|');
            $substances = trim((string)($values[$map['active_substance']] ?? ''));

            fputcsv($handle, [$product, $country, $routes, $substances]);
        }

        fclose($handle);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return true;
    }
}
