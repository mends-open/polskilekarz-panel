<?php

namespace App\Jobs;

use App\Models\Medication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpsertMedicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int, string> */
    public function __construct(private array $names)
    {
    }

    public function handle(): void
    {
        foreach ($this->names as $name) {
            Medication::firstOrCreate(['name' => $name]);
        }
    }
}
