<?php

namespace App\Jobs;

use App\Models\Medication;
use App\Models\MedicationBrand;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpsertMedicationBrandsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /** @var array<int, array{inn:string,brand:string,country:string,administration:string,form:?string,strength:?string}> */
    public function __construct(private array $records)
    {
    }

    public function handle(): void
    {
        foreach ($this->records as $record) {
            $medication = Medication::firstOrCreate(['inn' => $record['inn']]);

            MedicationBrand::firstOrCreate([
                'medication_id' => $medication->id,
                'country' => $record['country'],
                'brand' => $record['brand'],
                'administration' => $record['administration'],
                'form' => $record['form'],
                'strength' => $record['strength'],
            ]);
        }
    }
}
