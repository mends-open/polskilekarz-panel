<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use App\Enums\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicationBrand extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'medication_id',
        'country',
        'brand',
        'administration',
        'form',
        'strength',
    ];

    protected $casts = [
        'country' => Country::class,
    ];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'exists:medications,id'],
            'country' => ['required', 'string', 'size:2'],
            'brand' => ['required', 'string'],
            'administration' => ['required', 'string'],
            'form' => ['nullable', 'string'],
            'strength' => ['nullable', 'string'],
        ];
    }
}
