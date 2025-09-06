<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'name',
    ];

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'entry_medication')
            ->wherePivotNull('deleted_at');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}
