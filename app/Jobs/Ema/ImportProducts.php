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

class ImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        $handle = fopen($this->path, 'r');
        if (!$handle) {
            Log::warning('Unable to open EMA chunk for import', ['path' => $this->path]);
            return;
        }

        Log::info('Importing EMA products from CSV chunk', ['path' => $this->path]);

        $products = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$productName, $countryName, $routeNames, $substanceNames] = array_pad($row, 4, null);

            $productName = trim($productName);
            if ($productName === '') {
                continue;
            }

            $country = Country::tryFromName($countryName ?? '');
            if (!$country) {
                continue;
            }
            $countryValue = $country->value;

            $routes = collect(preg_split('/[|,;\r\n]+/', $routeNames ?? '', -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter()
                ->map->value
                ->all();

            $substances = collect(preg_split('/[|,;\r\n]+/', $substanceNames ?? '', -1, PREG_SPLIT_NO_EMPTY))
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

        fclose($handle);

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
            'path' => $this->path,
        ]);
    }
}
