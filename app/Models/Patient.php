<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class Patient extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes, AutomaticDateFormat;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'addresses',
        'identifiers',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'addresses' => 'array',
        'identifiers' => 'array',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(Email::class)
            ->using(EmailPatient::class)
            ->withPivot(['primary_since', 'message_consent_since'])
            ->withTimestamps();
    }

    public function phones(): BelongsToMany
    {
        return $this->belongsToMany(Phone::class)
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
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string'],
            'addresses' => ['nullable', 'array'],
            'identifiers' => ['nullable', 'array'],
        ];
    }
}
