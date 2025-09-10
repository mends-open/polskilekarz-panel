<?php

namespace App\Jobs\Ema;

use App\Enums\EmaProduct\Country;
use App\Enums\EmaProduct\RouteOfAdministration;
use App\Models\EmaProduct;
use App\Models\EmaSubstance;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileObject;

class ImportEmaProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public string $path,
        public int $startRow,
        public int $endRow,
    ) {
    }

    public function handle(): void
    {
        $disk = config('services.european_medicines_agency.storage_disk');
        $store = Storage::disk($disk);
        $csvRelative = Str::replaceLast('.xlsx', '.csv', $this->path);

        if (!$store->exists($csvRelative)) {
            Log::warning('EMA CSV chunk missing', ['path' => $store->path($csvRelative)]);
            return;
        }

        $file = new SplFileObject($store->path($csvRelative));
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $rowNumber = 0;
        $products = [];

        foreach ($file as $row) {
            $rowNumber++;
            if ($rowNumber < $this->startRow) {
                continue;
            }
            if ($rowNumber > $this->endRow) {
                break;
            }
            if (count($row) < 4) {
                continue;
            }

            [$productName, $countryName, $routeNames, $substanceNames] = $row;
            $productName = trim((string) $productName);
            if ($productName === '') {
                continue;
            }

            $country = Country::tryFromName(trim((string) $countryName));
            if (!$country) {
                continue;
            }
            $countryValue = $country->value;

            $routes = collect(explode('|', (string) $routeNames))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter()
                ->map->value
                ->all();

            $substances = collect(explode('|', (string) $substanceNames))
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

        Log::info('Imported EMA products from CSV chunk.', [
            'count' => count($products),
            'range' => [$this->startRow, $this->endRow],
            'path' => $csvRelative,
        ]);
    }
}
