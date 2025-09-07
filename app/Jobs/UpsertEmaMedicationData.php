<?php

namespace App\Jobs;

use App\Models\ActiveSubstance;
use App\Models\MedicinalProduct;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UpsertEmaMedicationData implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        $sheet = IOFactory::load($this->path)->getActiveSheet();
        $headerRow = 20;
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();

        $headers = [];
        for ($col = 1; $col <= $highestColumn; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $headers[$col] = Str::snake((string) $sheet->getCell($column.$headerRow)->getValue());
        }

        $products = [];
        $substances = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumn; $col++) {
                $column = Coordinate::stringFromColumnIndex($col);
                $rowData[$headers[$col]] = trim((string) $sheet->getCell($column.$row)->getValue());
            }

            $product = $rowData['product_name'] ?? '';
            if ($product !== '') {
                $products[] = ['name' => $product];
            }

            foreach (explode('|', $rowData['active_substance'] ?? '') as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $substances[] = ['name' => $name];
                }
            }
        }

        $products = collect($products)->unique('name')->values()->all();
        $substances = collect($substances)->unique('name')->values()->all();

        MedicinalProduct::upsert($products, ['name']);
        ActiveSubstance::upsert($substances, ['name']);

        Log::info('Upserted '.count($products).' products and '.count($substances).' active substances.');
    }
}
