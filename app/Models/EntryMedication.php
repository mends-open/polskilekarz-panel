<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;
use App\Models\Entry;
use App\Models\EmaActiveSubstance;

class EntryMedication extends Model
{
    use HasFactory, AutomaticDateFormat;

    protected $table = 'entry_medication';

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(EmaActiveSubstance::class, 'medication_id');
    }
}

