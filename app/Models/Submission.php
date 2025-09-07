<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class Submission extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes, AutomaticDateFormat;

    protected $fillable = [
        'patient_id',
        'user_id',
        'type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
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
            'data' => ['nullable', 'array'],
        ];
    }
}
