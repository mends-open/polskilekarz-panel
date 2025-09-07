<?php

namespace App\Jobs;

use App\Enums\Medication\Country;
use App\Enums\Medication\RouteOfAdministration;
use App\Models\ActiveSubstance;
use App\Models\Medication;
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

class ImportEmaMedications implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumn; $col++) {
                $column = Coordinate::stringFromColumnIndex($col);
                $rowData[$headers[$col]] = trim((string) $sheet->getCell($column.$row)->getValue());
            }

            $productName = $rowData['product_name'] ?? '';
            if ($productName === '') {
                continue;
            }

            $product = MedicinalProduct::firstWhere('name', $productName);
            if (!$product) {
                continue;
            }

            $country = Country::tryFromName($rowData['product_authorisation_country'] ?? '');
            if (!$country) {
                continue;
            }

            $routes = collect(explode('|', $rowData['route_of_administration'] ?? ''))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter();

            $substances = collect(explode('|', $rowData['active_substance'] ?? ''))
                ->map(fn ($s) => ActiveSubstance::firstWhere('name', trim($s)))
                ->filter();
            foreach ($substances as $substance) {
                foreach ($routes as $route) {
                    $medication = Medication::withTrashed()->firstOrCreate([
                        'active_substance_id' => $substance->id,
                        'medicinal_product_id' => $product->id,
                        'country' => $country,
                        'route_of_administration' => $route,
                    ]);

                    if ($medication->trashed()) {
                        $medication->restore();
                    }
                }
            }
        }

        Log::info('Imported medications from EMA sheet.');
    }
}
