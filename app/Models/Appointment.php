<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'type',
        'duration',
        'scheduled_at',
        'confirmed_at',
        'started_at',
        'cancelled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'string'],
            'duration' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'confirmed_at' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
        ];
    }
}
