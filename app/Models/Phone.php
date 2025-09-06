<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phone extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'phone',
    ];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class)
            ->using(PatientPhone::class)
            ->withPivot([
                'primary_since',
                'call_consent_since',
                'sms_consent_since',
                'whatsapp_consent_since',
            ])
            ->withTimestamps();
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
        ];
    }
}
