<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class EmaActiveSubstance extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes, AutomaticDateFormat;

    protected $table = 'ema_active_substances';

    protected $fillable = [
        'name',
    ];

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'entry_medication', 'medication_id', 'entry_id')
            ->using(EntryMedication::class)
            ->withTimestamps();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}
