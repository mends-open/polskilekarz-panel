<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class Entry extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes, AutomaticDateFormat;

    protected $fillable = [
        'patient_id',
        'user_id',
        'entity_id',
        'type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medications(): BelongsToMany
    {
        return $this->belongsToMany(Medication::class)
            ->using(EntryMedication::class)
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class)
            ->using(DocumentEntry::class)
            ->withTimestamps();
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'user_id' => ['required', 'exists:users,id'],
            'entity_id' => ['required', 'exists:entities,id'],
            'type' => ['required', 'string'],
            'data' => ['nullable', 'array'],
        ];
    }
}
