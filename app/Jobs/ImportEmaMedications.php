<?php

namespace App\Jobs;

use App\Enums\EMAProduct\Country;
use App\Enums\EMAProduct\RouteOfAdministration;
use App\Models\EMASubstance;
use App\Models\Medication;
use App\Models\EMAProduct;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEmaMedications implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        Log::info('Importing EMA medications from CSV chunk', ['path' => $this->path]);

        $productIds = [];
        $substanceIds = [];
        $medicationRows = [];
        $seen = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$productName, $countryName, $routeNames, $substanceNames] = array_pad($row, 4, null);

            $productName = trim($productName);
            if ($productName === '') {
                continue;
            }

            $productId = $productIds[$productName] ?? null;
            if (!$productId) {
                $product = EMAProduct::firstOrCreate(['name' => $productName]);
                $productId = $productIds[$productName] = $product->id;
            }

            $country = Country::tryFromName($countryName ?? '');
            if (!$country) {
                continue;
            }

            $routes = collect(explode('|', $routeNames ?? ''))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter();

            $substanceName = trim($substanceNames ?? '');
            $substances = $substanceName === ''
                ? collect()
                : collect([
                    $substanceIds[$substanceName]
                        ??= EMASubstance::firstOrCreate(['name' => $substanceName])->id,
                ]);

            foreach ($substances as $substanceId) {
                foreach ($routes as $route) {
                    $key = $substanceId.'|'.$productId.'|'.$country->value.'|'.$route->value;
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;

                    $medicationRows[] = [
                        'active_substance_id' => $substanceId,
                        'medicinal_product_id' => $productId,
                        'country' => $country->value,
                        'route_of_administration' => $route->value,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ];
                }
            }
        }

        fclose($handle);

        foreach (array_chunk($medicationRows, 500) as $chunk) {
            Medication::insertOrIgnore($chunk);

            foreach ($chunk as $row) {
                Medication::withTrashed()
                    ->where('active_substance_id', $row['active_substance_id'])
                    ->where('medicinal_product_id', $row['medicinal_product_id'])
                    ->where('country', $row['country'])
                    ->where('route_of_administration', $row['route_of_administration'])
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            }
        }

        Log::info('Imported medications from EMA chunk.', [
            'count' => count($medicationRows),
            'path' => $this->path,
        ]);
    }
}
