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

class UpsertEmaMedicationData implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        $handle = fopen($this->path, 'r');
        if (!$handle) {
            Log::warning('Unable to open EMA chunk for upsert', ['path' => $this->path]);
            return;
        }

        Log::info('Parsing EMA CSV chunk for upsert', ['path' => $this->path]);

        $products = [];
        $substances = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$product, $country, $routes, $active] = $row;

            $product = trim($product);
            if ($product !== '') {
                $products[] = ['name' => $product];
            }

            foreach (explode('|', $active ?? '') as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $substances[] = ['name' => $name];
                }
            }
        }

        fclose($handle);

        $products = collect($products)->unique('name')->values()->all();
        $substances = collect($substances)->unique('name')->values()->all();

        MedicinalProduct::upsert($products, ['name']);
        ActiveSubstance::upsert($substances, ['name']);

        Log::info('Upserted '.count($products).' products and '.count($substances).' active substances.');
    }
}
