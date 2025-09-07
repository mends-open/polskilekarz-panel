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
use Spatie\SimpleExcel\SimpleExcelReader;

class UpsertEmaMedicationData implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        $rows = SimpleExcelReader::create($this->path)
            ->headerOnRow(20)
            ->headersToSnakeCase()
            ->getRows();

        $products = [];
        $substances = [];

        foreach ($rows as $row) {
            $product = trim($row['product_name'] ?? '');
            if ($product !== '') {
                $products[] = ['name' => $product];
            }

            foreach (explode('|', $row['active_substance'] ?? '') as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $substances[] = ['name' => $name];
                }
            }
        }

        $products = collect($products)->unique('name')->values()->all();
        $substances = collect($substances)->unique('name')->values()->all();

        MedicinalProduct::upsert($products, ['name']);
        ActiveSubstance::upsert($substances, ['name']);

        Log::info('Upserted '.count($products).' products and '.count($substances).' active substances.');
    }
}
