<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Casts\IntegerArrayCast;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class EmaProduct extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'ema_substance_id',
        'name',
        'routes_of_administration',
        'countries',
        'metadata',
    ];

    protected $casts = [
        'routes_of_administration' => IntegerArrayCast::class,
        'countries' => IntegerArrayCast::class,
        'metadata' => 'array',
    ];

    public function substance(): BelongsTo
    {
        return $this->belongsTo(EmaSubstance::class, 'ema_substance_id');
    }

    public function rules(): array
    {
        return [
            'ema_substance_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
