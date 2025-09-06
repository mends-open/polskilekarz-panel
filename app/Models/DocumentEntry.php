<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DocumentEntry extends Pivot
{
    use HasFactory, ValidatesAttributes;

    protected $table = 'document_entry';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'document_id',
        'entry_id',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'exists:documents,id'],
            'entry_id' => ['required', 'exists:entries,id'],
        ];
    }
}
