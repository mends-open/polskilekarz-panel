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

        $this->convertToCsv($sourcePath, $targetPath);

        Log::info('Converted EMA spreadsheet to CSV', [
            'path' => $targetPath,
        ]);

        ChunkProductsCsv::dispatch($this->target);
    }

    private function convertToCsv(string $xlsxPath, string $csvPath): void
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($xlsxPath);

        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save($csvPath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}
