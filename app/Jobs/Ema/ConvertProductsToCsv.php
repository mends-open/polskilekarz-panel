<?php

namespace App\Jobs\Ema;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ConvertProductsToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public string $source, public string $target)
    {
    }

    public function handle(): void
    {
        $disk = config('services.european_medicines_agency.storage_disk');
        $store = Storage::disk($disk);
        $sourcePath = $store->path($this->source);
        $targetPath = $store->path($this->target);

        if ($this->convert($sourcePath, $targetPath)) {
            Log::info('Converted EMA spreadsheet to CSV', ['path' => $targetPath]);

            ChunkProductsCsv::dispatch($this->target);
        }
    }

    private function convert(string $xlsxPath, string $csvPath): bool
    {
        try {
            $spreadsheet = IOFactory::load($xlsxPath);
            $writer = IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\n");
            $writer->save($csvPath);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return true;
        } catch (\Throwable $e) {
            Log::warning('EMA spreadsheet conversion failed', [
                'file' => $xlsxPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

