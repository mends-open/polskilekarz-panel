<?php

namespace App\Jobs;

use App\Enums\MedicationBrandCountry;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        $brands = [];
        foreach ($sheet->toArray() as $index => $row) {
            if ($index < 20) {
                continue; // Skip headers and preamble
            }
            $brand = Str::of($row[0] ?? '')->squish()->value();
            $column = $row[1] ?? null;
            $administration = Str::of($row[2] ?? '')->squish()->value();
            $countryName = Str::of($row[3] ?? '')->squish()->value();
            $countryKey = $this->normalizeCountry($countryName);
            $country = MedicationBrandCountry::tryFrom($countryKey);
            if (! $column || ! $country) {
                continue;
            }

            $substances = explode('|', $column);
            foreach ($substances as $substance) {
                $inn = $this->normalize($substance);
                if ($inn === '') {
                    continue;
                }
                $unique[$inn] = true;

                $key = $inn.'|'.$brand.'|'.$country->value.'|'.$administration;
                $brands[$key] = [
                    'inn' => $inn,
                    'brand' => $brand,
                    'country' => $country->value,
                    'administration' => $administration,
                    'form' => null,
                    'strength' => null,
                ];
            }
        }

        $inns = array_keys($unique);
        $innChunks = array_chunk($inns, 500);
        $brandChunks = array_chunk(array_values($brands), 500);

        $batch = Bus::batch([])->name('Import EMA medications')->dispatch();
        foreach ($innChunks as $chunk) {
            $batch->add(new UpsertMedicationsJob($chunk));
        }
        foreach ($brandChunks as $chunk) {
            $batch->add(new UpsertMedicationBrandsJob($chunk));
        }
    }

    public function failed(Throwable $e): void
    {
        // Optionally handle failures
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->squish()
            ->value();
    }

    private function normalizeCountry(string $value): string
    {
        $key = Str::of($value)
            ->ascii()
            ->lower()
            ->replace(['(', ')'], '')
            ->snake()
            ->value();

        return $key === 'european_union' ? 'eu' : $key;
    }
}
