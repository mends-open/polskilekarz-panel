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
use Illuminate\Database\QueryException;

class UpsertEmaMedicationData implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            [$product, , , $active] = array_pad($row, 4, null);

            $product = trim($product);
            if ($product !== '') {
                $products[] = [
                    'name' => $product,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $active = trim($active ?? '');
            if ($active !== '') {
                $substances[] = [
                    'name' => $active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        fclose($handle);

        $products = collect($products)
            ->unique(fn ($item) => mb_strtolower($item['name']))
            ->values();
        $substances = collect($substances)
            ->unique(fn ($item) => mb_strtolower($item['name']))
            ->values();

        $products->chunk(1000)->each(function ($chunk) {
            try {
                MedicinalProduct::insertOrIgnore($chunk->all());
            } catch (QueryException $e) {
                Log::warning('Product insert failed', ['error' => $e->getMessage()]);
            }
        });

        $substances->chunk(1000)->each(function ($chunk) {
            try {
                ActiveSubstance::insertOrIgnore($chunk->all());
            } catch (QueryException $e) {
                Log::warning('Substance insert failed', ['error' => $e->getMessage()]);
            }
        });

        Log::info('Upserted products and active substances', [
            'products' => $products->count(),
            'substances' => $substances->count(),
            'path' => $this->path,
        ]);
    }
}
