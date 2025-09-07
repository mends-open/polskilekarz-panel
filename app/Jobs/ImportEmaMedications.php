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

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            [$productName, $countryName, $routeNames, $substanceNames] = $row;

            $productName = trim($productName);
            if ($productName === '') {
                continue;
            }

            $product = MedicinalProduct::firstWhere('name', $productName);
            if (!$product) {
                continue;
            }

            $country = Country::tryFromName($countryName ?? '');
            if (!$country) {
                continue;
            }

            $routes = collect(explode('|', $routeNames ?? ''))
                ->map(fn ($r) => RouteOfAdministration::tryFromName(trim($r)))
                ->filter();

            $substances = collect(explode('|', $substanceNames ?? ''))
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

                    $imported++;
                }
            }
        }

        fclose($handle);

        Log::info('Imported medications from EMA chunk.', ['count' => $imported, 'path' => $this->path]);
    }
}
