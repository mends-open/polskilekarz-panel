<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentEntry extends Pivot
{
    use SoftDeletes, ValidatesAttributes;

    protected $table = 'document_entry';

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
