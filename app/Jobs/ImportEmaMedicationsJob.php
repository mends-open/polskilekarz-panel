<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportEmaMedicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(private string $url)
    {
    }

    public function handle(): void
    {
        $tempPath = storage_path('app/ema-medications.xlsx');
        $response = Http::timeout(120)->get($this->url);
        file_put_contents($tempPath, $response->body());

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $unique = [];
        foreach ($sheet->toArray() as $index => $row) {
            if ($index < 20) {
                continue; // Skip headers and preamble
            }
            $column = $row[1] ?? null;
            if (! $column) {
                continue;
            }
            $substances = preg_split('/[|,]/', $column);
            foreach ($substances as $substance) {
                $name = strtolower(trim($substance));
                if ($name === '') {
                    continue;
                }
                $unique[$name] = true;
            }
        }

        $names = array_keys($unique);
        $chunks = array_chunk($names, 500);
        $batch = Bus::batch([])->name('Import EMA medications')->dispatch();
        foreach ($chunks as $chunk) {
            $batch->add(new UpsertMedicationsJob($chunk));
        }
    }

    public function failed(Throwable $e): void
    {
        // Optionally handle failures
    }
}
