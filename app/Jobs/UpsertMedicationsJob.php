<?php

namespace App\Jobs;

use App\Models\Medication;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class UpsertMedicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /** @var array<int, string> */
    public function __construct(private array $inns)
    {
    }

    public function handle(): void
    {
        foreach ($this->inns as $inn) {
            $normalized = Str::of($inn)->ascii()->lower()->squish()->value();
            if ($normalized !== '') {
                Medication::firstOrCreate(['inn' => $normalized]);
            }
        }
    }
}
