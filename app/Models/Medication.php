<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'inn',
    ];

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class)
            ->using(EntryMedication::class)
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function brands(): HasMany
    {
        return $this->hasMany(MedicationBrand::class);
    }

    public function rules(): array
    {
        return [
            'inn' => ['required', 'string'],
        ];
    }
}
