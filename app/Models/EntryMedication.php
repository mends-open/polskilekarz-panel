<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntryMedication extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $table = 'entry_medication';

    protected $fillable = [
        'entry_id',
        'medication_id',
        'user_id',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rules(): array
    {
        return [
            'entry_id' => ['required', 'exists:entries,id'],
            'medication_id' => ['required', 'exists:medications,id'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
