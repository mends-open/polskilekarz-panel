<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class EmaMedication extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $table = 'ema_medications';

    protected $fillable = [
        'active_substance_id',
        'medicinal_product_id',
        'countries',
        'routes_of_administration',
    ];

    protected $casts = [
        'countries' => 'array',
        'routes_of_administration' => 'array',
    ];

    public function activeSubstance(): BelongsTo
    {
        return $this->belongsTo(EmaActiveSubstance::class, 'active_substance_id');
    }

    public function medicinalProduct(): BelongsTo
    {
        return $this->belongsTo(EmaMedicinalProduct::class, 'medicinal_product_id');
    }

    public function rules(): array
    {
        return [
            'active_substance_id' => ['required', 'exists:ema_active_substances,id'],
            'medicinal_product_id' => ['required', 'exists:ema_medicinal_products,id'],
            'countries' => ['required', 'array'],
            'routes_of_administration' => ['required', 'array'],
        ];
    }
}
