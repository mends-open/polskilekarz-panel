<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientPhone extends Pivot
{
    use SoftDeletes, ValidatesAttributes;

    protected $table = 'patient_phone';

    protected $fillable = [
        'patient_id',
        'phone_id',
        'primary_since',
        'call_consent_since',
        'sms_consent_since',
        'whatsapp_consent_since',
    ];

    protected $casts = [
        'primary_since' => 'datetime',
        'call_consent_since' => 'datetime',
        'sms_consent_since' => 'datetime',
        'whatsapp_consent_since' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'phone_id' => ['required', 'exists:phones,id'],
            'primary_since' => ['nullable', 'date'],
            'call_consent_since' => ['nullable', 'date'],
            'sms_consent_since' => ['nullable', 'date'],
            'whatsapp_consent_since' => ['nullable', 'date'],
        ];
    }
}
