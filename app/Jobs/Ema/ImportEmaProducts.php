<?php

namespace App\Jobs\Ema;

use App\Enums\EmaProduct\Country;
use App\Enums\EmaProduct\RouteOfAdministration;
use App\Models\EmaProduct;
use App\Models\EmaSubstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportEmaProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public int $startRow,
        public int $endRow,
        public array $map,
    ) {
    }

    public function handle(): void
    {
        $disk = config('services.european_medicines_agency.storage_disk');
        $store = Storage::disk($disk);
        $xlsxPath = $store->path($this->path);

        $filter = new class($this->startRow, $this->endRow) implements IReadFilter {
            public function __construct(private int $start, private int $end) {}
            public function readCell($column, $row, $worksheetName = ''): bool
            {
                return $row >= $this->start && $row <= $this->end;
            }
        };

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setReadFilter($filter);
        $sheet = $reader->load($xlsxPath)->getActiveSheet();

        $products = [];

        for ($row = $this->startRow; $row <= $this->endRow; $row++) {
            $productCoordinate = Coordinate::stringFromColumnIndex($this->map['product_name']) . $row;
            $productName = trim((string) $sheet->getCell($productCoordinate)->getValue());
            if ($productName === '') {
                continue;
            }

            $countryCoordinate = Coordinate::stringFromColumnIndex($this->map['product_authorisation_country']) . $row;
            $routesCoordinate = Coordinate::stringFromColumnIndex($this->map['route_of_administration']) . $row;
            $substancesCoordinate = Coordinate::stringFromColumnIndex($this->map['active_substance']) . $row;

            $countryName = (string) $sheet->getCell($countryCoordinate)->getValue();
            $routeNames = (string) $sheet->getCell($routesCoordinate)->getValue();
            $substanceNames = (string) $sheet->getCell($substancesCoordinate)->getValue();

            $country = Country::tryFromName(trim($countryName));
            if (!$country) {
                continue;
            }
            $countryValue = $country->value;

            $routes = collect(explode('|', $routeNames))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter()
                ->map->value
                ->all();

            $substances = collect(explode('|', $substanceNames))
                ->map(fn ($s) => trim($s))
                ->filter();

            foreach ($substances as $substanceName) {
                $substance = EmaSubstance::firstOrCreate(['name' => $substanceName]);
                $key = $substance->id.'|'.mb_strtolower($productName);

                $product = $products[$key] ?? [
                    'ema_substance_id' => $substance->id,
                    'name' => $productName,
                    'routes_of_administration' => [],
                    'countries' => [],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ];

                $product['routes_of_administration'] = array_values(array_unique(array_merge(
                    $product['routes_of_administration'],
                    $routes
                )));
                $product['countries'] = array_values(array_unique(array_merge(
                    $product['countries'],
                    [$countryValue]
                )));

                $products[$key] = $product;
            }
        }

        $substanceIds = array_unique(array_column($products, 'ema_substance_id'));
        $existing = EmaProduct::whereIn('ema_substance_id', $substanceIds)
            ->get(['ema_substance_id', 'name', 'routes_of_administration', 'countries']);

        foreach ($existing as $record) {
            $key = $record->ema_substance_id.'|'.mb_strtolower($record->name);
            if (!isset($products[$key])) {
                continue;
            }

            $products[$key]['routes_of_administration'] = array_values(array_unique(array_merge(
                $record->routes_of_administration ?? [],
                $products[$key]['routes_of_administration']
            )));
            $products[$key]['countries'] = array_values(array_unique(array_merge(
                $record->countries ?? [],
                $products[$key]['countries']
            )));
        }

        foreach (array_chunk($products, 500, true) as $chunk) {
            $records = array_map(function ($product) {
                $model = new EmaProduct();
                $model->forceFill($product);
                return $model->getAttributes();
            }, $chunk);

            EmaProduct::upsert(
                array_values($records),
                ['ema_substance_id', 'name'],
                ['routes_of_administration', 'countries', 'updated_at', 'deleted_at']
            );
        }

        Log::info('Imported EMA products from chunk.', [
            'count' => count($products),
            'range' => [$this->startRow, $this->endRow],
            'path' => $this->path,
        ]);
    }
}
