<?php

namespace App\Jobs;

use App\Enums\EMAProduct\Country;
use App\Enums\EMAProduct\RouteOfAdministration;
use App\Models\EMAProduct;
use App\Models\EMASubstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEmaProducts implements ShouldQueue
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

            $routes = collect(explode('|', $routeNames ?? ''))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter()
                ->map->value
                ->all();

            $substances = collect(explode('|', $substanceNames ?? ''))
                ->map(fn ($s) => trim($s))
                ->filter();

            foreach ($substances as $substanceName) {
                $substance = EMASubstance::firstOrCreate(['name' => $substanceName]);
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

        foreach (array_chunk($products, 500, true) as $chunk) {
            EMAProduct::upsert(
                array_values($chunk),
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
