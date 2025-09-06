<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmailPatient extends Pivot
{
    use HasFactory, ValidatesAttributes;

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'email_id',
        'primary_since',
        'message_consent_since',
    ];

    protected $casts = [
        'primary_since' => 'datetime',
        'message_consent_since' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'email_id' => ['required', 'exists:emails,id'],
            'primary_since' => ['nullable', 'date'],
            'message_consent_since' => ['nullable', 'date'],
        ];
    }
}
