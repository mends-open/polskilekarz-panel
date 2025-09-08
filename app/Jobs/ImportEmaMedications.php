<?php

namespace App\Jobs;

use App\Enums\Medication\Country;
use App\Enums\Medication\RouteOfAdministration;
use App\Models\EmaActiveSubstance;
use App\Models\EmaMedication;
use App\Models\EmaMedicinalProduct;
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
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$productName, $countryName, $routeNames, $substanceNames] = array_pad($row, 4, null);

            $productName = trim($productName);
            if ($productName === '') {
                continue;
            }

            $productId = $productIds[$productName] ?? null;
            if (!$productId) {
                $product = EmaMedicinalProduct::firstOrCreate(['name' => $productName]);
                $productId = $productIds[$productName] = $product->id;
            }

            $country = Country::tryFromName($countryName ?? '');
            if (!$country) {
                continue;
            }

            $routes = collect(explode('|', $routeNames ?? ''))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter()
                ->map(fn ($r) => $r->value);

            $substances = collect(explode('|', $substanceNames ?? ''))
                ->map(fn ($s) => trim($s))
                ->filter()
                ->map(function ($name) use (&$substanceIds) {
                    return $substanceIds[$name]
                        ??= EmaActiveSubstance::firstOrCreate(['name' => $name])->id;
                });

            foreach ($substances as $substanceId) {
                $key = $productId.'|'.$substanceId;
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'active_substance_id' => $substanceId,
                        'medicinal_product_id' => $productId,
                        'countries' => [],
                        'routes_of_administration' => [],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ];
                }
                $rows[$key]['countries'][] = $country->value;
                $rows[$key]['routes_of_administration'] = array_merge(
                    $rows[$key]['routes_of_administration'],
                    $routes->all()
                );
            }
        }

        fclose($handle);

        foreach ($rows as &$row) {
            $row['countries'] = array_values(array_unique($row['countries']));
            $row['routes_of_administration'] = array_values(array_unique($row['routes_of_administration']));
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            EmaMedication::upsert(
                $chunk,
                ['active_substance_id', 'medicinal_product_id'],
                ['countries', 'routes_of_administration', 'deleted_at', 'updated_at']
            );
        }

        Log::info('Imported medications from EMA chunk.', [
            'count' => count($rows),
            'path' => $this->path,
        ]);
    }
}
