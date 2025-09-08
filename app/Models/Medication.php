<?php

namespace App\Models;

use App\Enums\Medication\Country;
use App\Enums\Medication\RouteOfAdministration;
use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class Medication extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'active_substance_id',
        'medicinal_product_id',
        'country',
        'route_of_administration',
    ];

    protected $casts = [
        'country' => Country::class,
        'route_of_administration' => RouteOfAdministration::class,
    ];

    public function activeSubstance(): BelongsTo
    {
        return $this->belongsTo(ActiveSubstance::class);
    }

    public function medicinalProduct(): BelongsTo
    {
        return $this->belongsTo(MedicinalProduct::class);
    }

    public function rules(): array
    {
        return [
            'active_substance_id' => ['required', 'exists:active_substances,id'],
            'medicinal_product_id' => ['required', 'exists:medicinal_products,id'],
            'country' => ['required', 'integer'],
            'route_of_administration' => ['required', 'integer'],
        ];
    }
}
