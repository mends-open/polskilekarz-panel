<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class EMAProduct extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'ema_substance_id',
        'name',
        'routes_of_administration',
        'countries',
    ];

    protected $casts = [
        'routes_of_administration' => 'array',
        'countries' => 'array',
    ];

    public function substance(): BelongsTo
    {
        return $this->belongsTo(EMASubstance::class, 'ema_substance_id');
    }

    public function rules(): array
    {
        return [
            'ema_substance_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
        ];
    }
}
