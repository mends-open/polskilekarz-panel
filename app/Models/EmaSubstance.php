<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class EmaSubstance extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(EmaProduct::class, 'ema_substance_id');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
